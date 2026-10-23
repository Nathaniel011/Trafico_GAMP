<?php
namespace Tests\Feature;

use App\Models\Caseta;
use App\Models\CasetaEvent;
use App\Models\TemporaryPhoto;
use App\Notice;
use App\User;
use Carbon\Carbon;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CasetaModernizationTest extends TestCase
{
    use \Tests\Concerns\IsolatedControlDatabase;
    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->useEnvironmentPath(sys_get_temp_dir());
        $app->loadEnvironmentFrom('certtrafico-modernization-tests-no-env');
        $app->make(Kernel::class)->bootstrap();
        return $app;
    }
    protected function setUp(): void
    {
        parent::setUp();
        // Exercise the reserved modules explicitly; normal installations keep both disabled.
        config(['features.caseta_dossier'=>true, 'features.recaudaciones'=>true]);
        $this->configureControlDatabase();
        $this->artisan('migrate', ['--force'=>true])->assertExitCode(0);
        Storage::fake('local'); Storage::fake('public');
        Carbon::setTestNow(Carbon::parse('2026-10-23 16:00:00', 'UTC'));
        $this->login();
    }
    protected function tearDown(): void { Carbon::setTestNow(); $this->releaseControlDatabase(); parent::tearDown(); }
    private function login(string $role = 'INSPECTOR_NOTIFICADOR', int $id = 1): User
    {
        $user = new User(); $user->id = $id; $user->name = 'Operador de prueba'; $user->roles = $role; $this->actingAs($user); return $user;
    }
    private function data(array $extra = []): array
    {
        return array_replace(['number'=>'C-001', 'owner'=>'Persona de prueba', 'document'=>'DOC-01', 'phone'=>'123', 'address'=>'Dirección de prueba',
            'lat'=>-19.589, 'lng'=>-65.754, 'location_confirmed'=>1, 'location_source'=>'MANUAL', 'location_reason'=>'Punto confirmado frente a la caseta'], $extra);
    }
    private function caseta(): Caseta
    {
        $this->post('/casetas', $this->data())->assertSessionHasNoErrors()->assertRedirect();
        return Caseta::firstOrFail();
    }
    private function payload(Caseta $caseta, string $number, string $date): array
    {
        return ['caseta_id'=>$caseta->id, 'mode'=>'SHOP', 'number'=>$number, 'date'=>$date, 'address'=>$caseta->address, 'lat'=>$caseta->lat, 'lng'=>$caseta->lng,
            'location_source'=>'EXISTING', 'location_confirmed'=>1, 'reason'=>'Control de ocupación', 'file'=>UploadedFile::fake()->image('foto.png')];
    }
    public function test_caseta_without_certificate_can_be_registered_and_read_on_all_screens(): void
    {
        $caseta = $this->caseta();
        $this->assertSame('POR VERIFICAR', $caseta->licenseStatus());
        $this->get('/casetas')->assertOk()->assertSee('C-001');
        $this->get('/casetas/'.$caseta->id)->assertOk()->assertSee('Historial de control');
        $this->get('/casetas/'.$caseta->id.'/editar')->assertOk();
        $this->get('/casetas/mapa')->assertOk()->assertDontSee('maps.googleapis.com');
        $this->getJson('/casetas/puntos')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('points.0.number', 'C-001');
        $this->get('/notices/create?caseta_id='.$caseta->id)->assertOk()->assertSee('Fotografía');
        $this->get('/certificate/create?caseta_id='.$caseta->id)->assertOk()->assertSee('Persona de prueba');
        $this->assertDatabaseCount('certificates', 0);
    }
    public function test_location_requires_confirmation_valid_ranges_and_a_manual_reason(): void
    {
        foreach ([['lat'=>91], ['lng'=>181], ['location_confirmed'=>0], ['location_reason'=>''], ['location_source'=>'EXISTING']] as $invalid) {
            $this->post('/casetas', $this->data($invalid))->assertSessionHasErrors();
        }
        $this->assertDatabaseCount('casetas', 0);
        $this->post('/casetas', $this->data(['location_source'=>'GPS', 'accuracy'=>5, 'located_at'=>'fecha-inválida']))->assertSessionHasErrors('located_at');
        $this->post('/casetas', $this->data(['location_source'=>'GPS', 'accuracy'=>5, 'located_at'=>now()->subMinutes(11)->toIso8601String()]))->assertSessionHasErrors('located_at');
        $this->post('/casetas', $this->data(['location_source'=>'GPS', 'accuracy'=>5, 'located_at'=>now()->toIso8601String()]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('casetas', ['location_source'=>'GPS','accuracy'=>5]);
    }
    public function test_three_notices_turn_red_at_exactly_48_hours_and_fourth_is_blocked(): void
    {
        $caseta = $this->caseta();
        foreach ([1=>'21/10/2026 09:00', 2=>'21/10/2026 10:00', 3=>'21/10/2026 12:00'] as $sequence=>$date) {
            $this->post('/notices/create', $this->payload($caseta, '00'.$sequence, $date))->assertSessionHasNoErrors()->assertRedirect();
            $this->assertSame([1=>'yellow',2=>'blue',3=>'red'][$sequence], $caseta->fresh()->controlState()['color']);
        }
        $third = Notice::where('sequence', 3)->firstOrFail();
        Carbon::setTestNow($third->deadline_at->copy()->subSecond());
        $this->assertSame('green', $caseta->fresh()->controlState()['color']);
        Carbon::setTestNow($third->deadline_at);
        $this->assertSame('red', $caseta->fresh()->controlState()['color']);
        $this->getJson('/casetas/puntos?stage=red')->assertJsonPath('total', 1)->assertJsonPath('points.0.control.sequence', 3);
        $this->getJson('/casetas/puntos?stage=green')->assertJsonPath('total', 0);
        $this->artisan('casetas:expire')->assertExitCode(0);
        $this->artisan('casetas:expire')->assertExitCode(0);
        $this->assertSame(1, CasetaEvent::where('kind', 'DEADLINE')->count());
        $this->post('/notices/create', $this->payload($caseta, '004', '23/10/2026 11:59'))->assertSessionHasErrors('caseta_id');
        $this->assertDatabaseCount('notices', 3);
        $this->get('/notices/3')->assertOk()->assertSee('48 horas');
    }
    public function test_search_does_not_mix_years_or_drop_leading_zeroes(): void
    {
        $caseta = $this->caseta();
        $this->post('/notices/create', $this->payload($caseta, '0012', '21/10/2025 09:00'))->assertSessionHasNoErrors();
        $this->post('/notices/create', $this->payload($caseta, '0012', '21/10/2026 09:00'))->assertSessionHasNoErrors();
        $this->get('/notices?mode=SHOPS&number=0012&year=2025')->assertOk()->assertSee('0012 / 2025')->assertDontSee('0012 / 2026');
        $this->post('/notices/create', $this->payload($caseta, '0012', '22/10/2026 09:00'))->assertSessionHasErrors('number');
    }
    public function test_legal_is_read_only_and_inspector_cannot_administer_or_authorize_retirement(): void
    {
        $caseta = $this->caseta();
        $this->post('/notices/create', $this->payload($caseta, '001', '22/10/2026 09:00'))->assertSessionHasNoErrors();
        $this->post('/notices/1/edit', ['owner'=>'P', 'document'=>'D', 'retirement'=>1])->assertForbidden();
        $this->get('/perfiles')->assertForbidden();
        $this->post('/inspectors/create', [])->assertForbidden();
        $this->login('ASESOR_LEGAL');
        $this->get('/casetas/'.$caseta->id)->assertOk();
        $this->get('/notices/1')->assertOk()->assertDontSee('Guardar actualización');
        $this->post('/casetas', $this->data())->assertForbidden();
        $this->post('/notices/create', $this->payload($caseta, '002', '23/10/2026 09:00'))->assertForbidden();
        $this->post('/certificate/create', [])->assertForbidden();
        $this->post('/groups/1/edit', [])->assertForbidden();
    }
    public function test_temporary_photo_is_private_owned_and_can_only_be_consumed_once(): void
    {
        $caseta = $this->caseta();
        $photo = $this->postJson('/notice-photos', ['file'=>UploadedFile::fake()->image('foto.png')])->assertCreated()->json();
        $this->get($photo['url'])->assertOk();
        $this->login('INSPECTOR_NOTIFICADOR', 2);
        $this->get($photo['url'])->assertNotFound();
        $payload = $this->payload($caseta, '001', '22/10/2026 09:00'); unset($payload['file']); $payload['photo_id']=$photo['id'];
        $this->post('/notices/create', $payload)->assertSessionHasErrors('photo_id');
        $this->login();
        $this->post('/notices/create', $payload)->assertSessionHasNoErrors();
        $this->get($photo['url'])->assertNotFound();
        $this->get('/notice-photos/1')->assertOk();
        $payload['number']='002'; $payload['date']='23/10/2026 09:00';
        $this->post('/notices/create', $payload)->assertSessionHasErrors('photo_id');
        $this->assertDatabaseCount('notices', 1);
        Carbon::setTestNow(now()->addDays(2));
        $this->artisan('casetas:cleanup-photos')->assertExitCode(0);
        $this->get('/notice-photos/1')->assertOk();
    }
    public function test_license_retirement_updates_and_case_closure_preserve_history(): void
    {
        $caseta = $this->caseta();
        $this->post('/notices/create', $this->payload($caseta, '001', '22/10/2026 09:00'))->assertSessionHasNoErrors();
        $this->login('JEFE_UNIDAD');
        $this->post('/casetas/'.$caseta->id.'/licencias', ['number'=>'L001','year'=>2026,'issued_on'=>'2026-01-01','expires_on'=>'2026-12-31','source'=>'Registro revisado'])->assertSessionHasNoErrors();
        $this->assertSame('white', $caseta->fresh()->licenseColor());
        $this->post('/notices/1/edit', ['owner'=>'Persona', 'document'=>'DOC', 'date2'=>'23/10/2026', 'retirement'=>'1'])->assertSessionHasNoErrors();
        $this->assertTrue(Notice::first()->retirement);
        $this->assertSame('red', $caseta->fresh()->licenseColor());
        $this->post('/notices/1/edit', ['owner'=>'Persona', 'document'=>'DOC', 'date2'=>'23/10/2026', 'retirement'=>'0'])->assertSessionHasNoErrors();
        $this->assertFalse(Notice::first()->retirement);
        $this->post('/casetas/'.$caseta->id.'/procedimientos/1/cerrar', ['reason'=>'Regularización verificada'])->assertSessionHasNoErrors();
        $this->assertSame('neutral', $caseta->fresh()->controlState()['color']);
        $this->assertDatabaseCount('notices', 1);
        $this->assertDatabaseHas('caseta_events', ['kind'=>'CASE_CLOSED']);
    }
    public function test_owner_and_location_changes_keep_previous_values_in_history(): void
    {
        $caseta = $this->caseta();
        $this->post('/casetas/'.$caseta->id.'/editar', $this->data(['owner'=>'Nuevo titular', 'lat'=>-19.59]))->assertSessionHasNoErrors();
        $event = CasetaEvent::where('kind','UPDATED')->firstOrFail();
        $this->assertSame('Persona de prueba', $event->details['before']['owner']);
        $this->assertSame('Nuevo titular', $event->details['after']['owner']);
        $this->assertEquals(-19.589, $event->details['before']['lat']);
        $this->assertEquals(-19.59, $event->details['after']['lat']);
    }

    private function certificatePayload(): array
    {
        return ['type'=>'CASETA','mode'=>'NUEVO','solicitante'=>'Persona de prueba','ci'=>'DOC-01','rubro'=>'Venta de dulces','direccion'=>'Dirección',
            'dim_ancho'=>1.5,'dim_largo'=>2,'tipo_via'=>'ACERA','dim_via'=>4,'ref_product'=>'VENTA DE DULCES','ref_turn'=>'DIURNO','ref_start'=>'08:00','ref_end'=>'12:00',
            'color'=>'Blanco','material'=>'Metal','inspection_date'=>'23/10/2026','technical_proceed'=>'0','legal_proceed'=>'1','lat'=>-19.589,'lng'=>-65.754,'conclusions'=>'Inspección no procedente.', 'location_source'=>'MANUAL', 'location_reason'=>'Punto revisado', 'location_confirmed'=>1];
    }
    public function test_negative_technical_result_and_repeated_owner_documents_are_preserved(): void
    {
        $this->post('/certificate/create', $this->certificatePayload())->assertSessionHasNoErrors();
        $certificate = \App\Models\Certificate::firstOrFail();
        $this->assertFalse($certificate->technical_proceed);
        $this->assertSame('Inspección no procedente.', $certificate->conclusions);
        $this->post('/certificate/create', $this->certificatePayload())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('certificates', 2);
        $this->post('/certificate/create', array_replace($this->certificatePayload(), ['lat'=>91]))->assertSessionHasErrors('lat');
        $this->login('JEFE_UNIDAD');
        $this->post('/certificate/edit/1', array_replace($this->certificatePayload(), ['lat'=>-19.59, 'revision_reason'=>'Corrección revisada', 'certificate_version'=>\App\Services\CertificateHistory::version($certificate)]))->assertSessionHasNoErrors();
        $this->assertSame('Inspección no procedente.', $certificate->fresh()->conclusions);
    }
    public function test_verified_missing_license_is_red_without_owner_declaration_x(): void
    {
        $this->post('/casetas', $this->data(['license_verification'=>'NO_LICENSE']))->assertSessionHasNoErrors();
        $this->getJson('/casetas/puntos?license=SIN%20LICENCIA')->assertOk()->assertJsonPath('total',1)
            ->assertJsonPath('points.0.license_color','red')->assertJsonPath('points.0.declared_no_license',false);
        $this->getJson('/casetas/puntos?license=POR%20VERIFICAR')->assertJsonPath('total',0);
    }
    public function test_existing_location_cannot_be_changed_without_gps_or_manual_reason(): void
    {
        $caseta=$this->caseta();
        $payload=$this->payload($caseta, '001', '22/10/2026 09:00'); $payload['lat']=-19.6;
        $this->post('/notices/create',$payload)->assertSessionHasErrors('location_source');
        $this->assertDatabaseCount('notices',0);
    }
    public function test_historical_linking_requires_supervisor_and_does_not_infer_a_deadline(): void
    {
        $caseta=$this->caseta();
        DB::table('notices')->insert(['type'=>'SHOPS','number'=>'ANT-001','notice_date'=>'2025-05-01 12:00:00','address'=>'Dirección','lat'=>-19.589,'lng'=>-65.754]);
        $this->post('/casetas/'.$caseta->id.'/vincular-aviso',['notice_id'=>1])->assertForbidden();
        $this->login('JEFE_UNIDAD');
        $this->post('/casetas/'.$caseta->id.'/vincular-aviso',['notice_id'=>1])->assertSessionHasNoErrors();
        $this->assertNull(Notice::first()->deadline_at);
        $this->assertSame('neutral',$caseta->fresh()->controlState()['color']);
        $this->post('/casetas/'.$caseta->id.'/vincular-aviso',['notice_id'=>1])->assertUnprocessable();
        $this->get('/notices?mode=SHOP&year=2025&number=ANT-001')->assertSee('ANT-001');
    }
    public function test_cleanup_removes_only_unconsumed_expired_photographs(): void
    {
        $photo=$this->postJson('/notice-photos',['file'=>UploadedFile::fake()->image('foto.png')])->assertCreated()->json();
        $record=TemporaryPhoto::findOrFail($photo['id']);
        Storage::disk('local')->assertExists($record->path);
        Carbon::setTestNow(now()->addDays(2));
        $this->artisan('casetas:cleanup-photos')->assertExitCode(0);
        Storage::disk('local')->assertMissing($record->path);
        $this->assertDatabaseCount('temporary_photos',0);
    }
    public function test_additive_migrations_can_be_rolled_back_without_deleting_legacy_notices(): void
    {
        DB::table('notices')->insert(['type'=>'SHOPS','number'=>'HIST-001','notice_date'=>'2025-05-01 12:00:00','address'=>'Dirección','lat'=>-19.589,'lng'=>-65.754]);
        $this->artisan('migrate:rollback',['--step'=>5,'--force'=>true])->assertExitCode(0);
        $this->assertDatabaseHas('notices',['number'=>'HIST-001']);
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('casetas'));
    }
    public function test_profiles_cannot_remove_the_last_supervisor(): void
    {
        $admin = new User(); $admin->id=1; $admin->name='Jefatura'; $admin->username='jefatura'; $admin->password=bcrypt('test-password'); $admin->roles='JEFE_UNIDAD'; $admin->save();
        $this->actingAs($admin);
        $this->get('/perfiles')->assertOk()->assertSee('Perfiles de acceso');
        $this->post('/perfiles/1',['role'=>'ASESOR_LEGAL'])->assertSessionHasErrors('role');
        $this->assertSame('JEFE_UNIDAD',$admin->fresh()->roles);
    }


    public function test_notice_year_uses_bolivia_date_at_the_utc_year_boundary(): void
    {
        $caseta=$this->caseta();
        $this->post('/notices/create',$this->payload($caseta,'FIN-001','31/12/2025 23:45'))->assertSessionHasNoErrors();
        $notice=Notice::firstOrFail();
        $this->assertSame(2025,$notice->year);
        $this->assertSame('2026-01-01 03:45:00',$notice->getRawOriginal('notice_date'));
        $this->assertSame('31/12/2025 23:45',$notice->formatted_date);
        $this->get('/notices?year=2025&number=FIN-001')->assertSee('FIN-001 / 2025');
        $this->get('/notices?year=2026&number=FIN-001')->assertDontSee('FIN-001 / 2025');
    }
    public function test_certificate_cannot_bypass_location_confirmation(): void
    {
        $payload=$this->certificatePayload();
        unset($payload['location_source'],$payload['location_confirmed'],$payload['location_reason']);
        $this->post('/certificate/create',$payload)->assertSessionHasErrors(['location_source','location_confirmed']);
        $this->assertDatabaseCount('certificates',0);
    }

    public function test_certificate_versions_preserve_content_and_reject_stale_edits(): void
    {
        $caseta = $this->caseta();
        $this->post('/certificate/create', $this->certificatePayload() + ['caseta_id'=>$caseta->id])->assertSessionHasNoErrors();
        $certificate = \App\Models\Certificate::firstOrFail();
        $version = \App\Services\CertificateHistory::version($certificate);
        $this->assertDatabaseCount('certificate_revisions', 1);
        $this->login('JEFE_UNIDAD');
        $this->get('/certificate/edit/'.$certificate->id)->assertOk()->assertSee('certificate_version', false)->assertSee('Inspección no procedente.');
        $payload = array_replace($this->certificatePayload(), ['lat'=>-19.5901,'conclusions'=>'Nueva observación revisada.', 'certificate_version'=>$version, 'revision_reason'=>'Corrección de inspección']);
        $this->post('/certificate/edit/'.$certificate->id, $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('certificate_revisions', 2);
        $this->assertSame('Inspección no procedente.', $certificate->revisions()->reorder('revision')->first()->snapshot['conclusions']);
        $this->assertEquals(-19.5901, $caseta->fresh()->lat);
        $this->post('/certificate/edit/'.$certificate->id, $payload)->assertSessionHasErrors('certificate_version');
        $this->assertDatabaseCount('certificate_revisions', 2);
        $this->assertSame('Nueva observación revisada.', $certificate->fresh()->conclusions);
        $this->login('ASESOR_LEGAL');
        $this->get('/certificate/history/'.$certificate->id)->assertOk()->assertSee('Corrección de inspección')->assertSee('Nueva observación revisada.');
        $this->post('/certificate/edit/'.$certificate->id, $payload)->assertForbidden();
    }

    public function test_certificate_existing_point_does_not_overwrite_a_newer_caseta_location(): void
    {
        $caseta = $this->caseta();
        $this->post('/certificate/create', $this->certificatePayload() + ['caseta_id'=>$caseta->id])->assertSessionHasNoErrors();
        $certificate = \App\Models\Certificate::firstOrFail();
        $caseta->update(['lat'=>-19.6]);
        $this->login('JEFE_UNIDAD');
        $payload = array_replace($this->certificatePayload(), ['location_source'=>'EXISTING','certificate_version'=>\App\Services\CertificateHistory::version($certificate),'revision_reason'=>'Revisión de datos']);
        $this->post('/certificate/edit/'.$certificate->id, $payload)->assertSessionHasErrors('location_source');
        $this->assertEquals(-19.6, $caseta->fresh()->lat);
        $this->assertDatabaseCount('certificate_revisions', 1);
    }

    public function test_new_notice_coordinates_are_shared_without_moving_previous_documents(): void
    {
        $caseta = $this->caseta();
        $first = $this->payload($caseta, 'SYNC-1', '22/10/2026 09:00');
        $this->post('/notices/create', $first)->assertSessionHasNoErrors();
        $second = array_replace($this->payload($caseta, 'SYNC-2', '23/10/2026 10:00'), ['lat'=>-19.6, 'location_source'=>'MANUAL','location_reason'=>'Verificación en campo']);
        $this->post('/notices/create', $second)->assertSessionHasNoErrors();
        $this->assertEquals(-19.6, $caseta->fresh()->lat);
        $this->assertEquals(-19.589, Notice::where('number','SYNC-1')->firstOrFail()->lat);
        $this->getJson('/casetas/puntos')->assertJsonPath('points.0.lat', -19.6);
        $this->assertDatabaseHas('caseta_events', ['kind'=>'LOCATION']);
    }

    public function test_legacy_queue_and_map_preserve_unlinked_documents_without_inferred_states(): void
    {
        $caseta = $this->caseta();
        $this->post('/certificate/create', $this->certificatePayload())->assertSessionHasNoErrors();
        DB::table('certificate_revisions')->delete();
        DB::table('certificates')->where('id',1)->update(['solicitante'=>'Histórico revisable','ci'=>'OLD-1','direccion'=>'Dirección antigua','lat'=>-19.59,'lng'=>-65.75,'inspection_date'=>'2024-04-01','created_at'=>'2024-04-01 08:00:00','updated_at'=>'2024-04-01 08:00:00']);
        DB::table('notices')->insert(['type'=>'SHOPS','number'=>'OLD-2','address'=>'Dirección antigua','lat'=>-19.59,'lng'=>-65.75,'notice_date'=>'2024-04-02 08:00:00']);
        $this->get('/casetas/documentos-historicos')->assertOk()->assertSee('Histórico revisable');
        $this->getJson('/casetas/puntos')->assertJsonPath('legacy_total',2)->assertJsonCount(2,'legacy_points');
        $this->getJson('/casetas/puntos?stage=red')->assertJsonPath('legacy_total',0);
        $this->post('/casetas/documentos-historicos/vincular',['kind'=>'certificates','document_id'=>1,'caseta_number'=>$caseta->number,'review_confirmed'=>1])->assertForbidden();
        $this->login('JEFE_UNIDAD');
        $this->post('/casetas/documentos-historicos/vincular',['kind'=>'certificates','document_id'=>1,'caseta_number'=>$caseta->number,'review_confirmed'=>1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('certificates',['id'=>1,'caseta_id'=>$caseta->id,'inspection_date'=>'2024-04-01','updated_at'=>'2024-04-01 08:00:00']);
        $this->assertDatabaseCount('certificate_revisions',2);
        $this->getJson('/casetas/puntos')->assertJsonPath('legacy_total',1);
        $this->assertSame('neutral',$caseta->fresh()->controlState()['color']);
    }

    private function paymentFixture(): array
    {
        $caseta = $this->caseta();
        $this->login('JEFE_UNIDAD');
        $this->post('/casetas/'.$caseta->id.'/licencias', ['number'=>'0007','year'=>2026,'issued_on'=>'2026-01-01','expires_on'=>'2026-12-31','source'=>'Registro revisado'])->assertSessionHasNoErrors();
        return [$caseta, $caseta->licenses()->firstOrFail()];
    }

    private function paymentData(array $extra = []): array
    {
        return array_replace(['estado'=>'PAID','fuente'=>'Exportación de prueba autorizada','referencia'=>'REC-001','verificado_el'=>'2026-10-23 10:00:00','observaciones'=>'Comprobante revisado'], $extra);
    }

    public function test_payment_verifications_preserve_sources_dates_and_latest_status(): void
    {
        [$caseta,$license] = $this->paymentFixture();
        $url = '/casetas/'.$caseta->id.'/licencias/'.$license->id.'/verificar-pago';
        $this->post($url,$this->paymentData())->assertSessionHasNoErrors();
        $this->post($url,$this->paymentData())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('license_payment_checks',1);
        $this->assertSame('PAID',$license->fresh()->payment_status);
        $this->post($url,$this->paymentData(['estado'=>'PENDING','referencia'=>'REC-ANTERIOR','verificado_el'=>'2026-10-22 10:00:00']))->assertSessionHasNoErrors();
        $this->assertSame('PAID',$license->fresh()->payment_status);
        $this->assertDatabaseCount('license_payment_checks',2);
        $this->assertSame('VIGENTE',$caseta->fresh()->licenseStatus());
        $this->get('/casetas/'.$caseta->id)->assertOk()->assertSee('REC-001')->assertSee('Exportación de prueba autorizada');
        $this->login('ASESOR_LEGAL');
        $this->get('/recaudaciones?q=0007&year=2026')->assertOk()->assertSee('Pagado');
        $this->post($url,$this->paymentData())->assertForbidden();
        $this->post('/recaudaciones/revisar',[])->assertForbidden();
    }

    private function csvFile(string $rows): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('recaudaciones.csv', implode(',',\App\Services\LicensePayments::COLUMNS)."\n".$rows);
    }

    public function test_csv_requires_preview_then_imports_once_and_rejects_tampering(): void
    {
        [$caseta,$license] = $this->paymentFixture();
        $row = $caseta->number.',0007,2026,PAID,Archivo revisado,REC-CSV,2026-10-23 10:00:00,Revisión de prueba';
        $this->post('/recaudaciones/revisar',['file'=>$this->csvFile($row)])->assertOk()->assertSee('REC-CSV');
        $this->assertDatabaseCount('license_payment_checks',0);
        $token = session('payments_preview.token');
        $this->post('/recaudaciones/importar',['token'=>str_repeat('x',64)])->assertUnprocessable();
        $this->post('/recaudaciones/importar',['token'=>$token,'rows'=>[['status'=>'PENDING']]])->assertSessionHasNoErrors()->assertRedirect('/recaudaciones');
        $this->assertSame('PAID',$license->fresh()->payment_status);
        $this->assertDatabaseCount('license_payment_checks',1);
        $this->post('/recaudaciones/importar',['token'=>$token])->assertUnprocessable();
        $this->post('/recaudaciones/revisar',['file'=>$this->csvFile($row)])->assertOk();
        $this->post('/recaudaciones/importar',['token'=>session('payments_preview.token')])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('license_payment_checks',1);
    }

    public function test_csv_rejects_unknown_licenses_and_future_dates_without_partial_changes(): void
    {
        [$caseta,$license] = $this->paymentFixture();
        $valid=$caseta->number.',0007,2026,PAID,Archivo,REC-1,2026-10-23 10:00:00,Revisado';
        $invalid=$caseta->number.',NO-EXISTE,2026,PAID,Archivo,REC-2,2026-10-23 10:00:00,Revisado';
        $this->post('/recaudaciones/revisar',['file'=>$this->csvFile($valid."\n".$invalid)])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('license_payment_checks',0);
        $this->post('/casetas/'.$caseta->id.'/licencias/'.$license->id.'/verificar-pago',$this->paymentData(['verificado_el'=>'2026-10-24 10:00:00']))->assertSessionHasErrors('verificado_el');
        $this->assertSame('UNVERIFIED',$license->fresh()->payment_status);
    }
    public function test_legacy_official_numbers_are_not_reused_in_the_same_year(): void
    {
        $caseta = $this->caseta();
        DB::table('notices')->insert(['type'=>'SHOPS','number'=>'0008','address'=>'Archivo histórico','lat'=>-19.589,'lng'=>-65.754,'notice_date'=>'2026-01-01 10:00:00']);
        $this->post('/notices/create',$this->payload($caseta,'0008','22/10/2026 09:00'))->assertSessionHasErrors('number');
        $this->assertDatabaseCount('notices',1);
        $this->post('/notices/create',$this->payload($caseta,'0008','22/10/2025 09:00'))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('notices',2);
    }

    public function test_reusing_a_point_preserves_its_measured_accuracy_and_capture_date(): void
    {
        $caseta = $this->caseta();
        $caseta->update(['location_source'=>'GPS','accuracy'=>6,'located_at'=>now('UTC')->subMinutes(2)]);
        $timestamp = $caseta->getRawOriginal('located_at');
        $this->post('/casetas/'.$caseta->id.'/editar',$this->data(['location_source'=>'EXISTING','owner'=>'Propietario actualizado']))->assertSessionHasNoErrors();
        $this->assertEquals(6,$caseta->fresh()->accuracy);
        $this->assertSame($timestamp,$caseta->fresh()->getRawOriginal('located_at'));
        $this->assertSame('GPS',$caseta->fresh()->location_source);
        $this->post('/notices/create',$this->payload($caseta,'GPS-1','22/10/2026 09:00'))->assertSessionHasNoErrors();
        $this->assertEquals(6,Notice::firstOrFail()->accuracy);
        $this->assertSame($timestamp,Notice::firstOrFail()->getRawOriginal('located_at'));
    }
    public function test_certificate_pdf_prints_without_a_static_map_service(): void
    {
        $this->post('/certificate/create',$this->certificatePayload())->assertSessionHasNoErrors();
        config(['services.mapbox.token'=>'']);
        \Illuminate\Support\Facades\Http::preventStrayRequests();
        ob_start();
        try { $response=$this->get('/certificate/pdf/1'); $pdf=ob_get_contents(); }
        finally { ob_end_clean(); }
        $response->assertOk();
        $this->assertStringStartsWith('%PDF-',$pdf);
        $this->assertStringEndsWith('%%EOF',rtrim($pdf));
        \Illuminate\Support\Facades\Http::assertNothingSent();
    }

    public function test_map_summary_uses_document_date_instead_of_import_order(): void
    {
        $caseta = $this->caseta();
        $this->post('/notices/create',$this->payload($caseta,'ACTUAL','22/10/2026 09:00'))->assertSessionHasNoErrors();
        DB::table('notices')->insert(['caseta_id'=>$caseta->id,'type'=>'SHOPS','number'=>'IMPORTADO','address'=>'Histórico','lat'=>$caseta->lat,'lng'=>$caseta->lng,'notice_date'=>'2024-01-01 10:00:00']);
        $this->getJson('/casetas/puntos')->assertJsonPath('points.0.last_notice_at','22/10/2026 09:00');
    }
    public function test_certificate_pdf_survives_a_failed_static_map_connection(): void
    {
        $this->post('/certificate/create',$this->certificatePayload())->assertSessionHasNoErrors();
        config(['services.mapbox.token'=>'token-ficticio']);
        \Illuminate\Support\Facades\Http::fake(['api.mapbox.com/*'=>\Illuminate\Support\Facades\Http::failedConnection()]);
        ob_start();
        try { $response=$this->get('/certificate/pdf/1'); $pdf=ob_get_contents(); }
        finally { ob_end_clean(); }
        $response->assertOk();
        $this->assertStringStartsWith('%PDF-',$pdf);
        $this->assertStringEndsWith('%%EOF',rtrim($pdf));
        \Illuminate\Support\Facades\Http::assertSentCount(1);
    }
}
