<?php
namespace Tests\Feature;

use App\Models\Caseta;
use App\Models\Certificate;
use App\Services\CertificateHistory;
use App\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ActiveScopeTest extends TestCase
{
    use \Tests\Concerns\IsolatedControlDatabase;

    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->useEnvironmentPath(sys_get_temp_dir());
        $app->loadEnvironmentFrom('certtrafico-scope-tests-no-env');
        $app->make(Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureControlDatabase();
        $this->artisan('migrate', ['--force'=>true])->assertExitCode(0);
        $user = new User(); $user->id=1; $user->name='Jefatura de prueba'; $user->roles='JEFE_UNIDAD';
        $this->actingAs($user);
    }

    protected function tearDown(): void
    {
        $this->releaseControlDatabase();
        parent::tearDown();
    }

    private function caseta(): Caseta
    {
        return Caseta::create(['number'=>'ALCANCE-01','owner'=>'Titular de prueba','document'=>'DOC-1','phone'=>'123',
            'address'=>'Dirección de prueba','lat'=>-19.589,'lng'=>-65.754,'location_source'=>'MANUAL']);
    }

    public function test_default_scope_hides_dossier_payments_and_keeps_the_operational_map(): void
    {
        $this->assertFalse(config('features.caseta_dossier'));
        $this->assertFalse(config('features.recaudaciones'));
        $caseta=$this->caseta();
        $this->get('/casetas/'.$caseta->id)->assertOk()->assertSee('Resumen')->assertSee('Notificaciones y procedimientos')
            ->assertDontSee('Recaudaciones')->assertDontSee('Historial de control')->assertDontSee('Certificados de inspección')
            ->assertDontSee('Primer registro')->assertDontSee('Vincular documentos históricos');
        $this->get('/casetas')->assertOk()->assertDontSee('Documentos históricos')->assertDontSee('Recaudaciones');
        $this->get('/casetas/mapa')->assertOk()->assertSee('Mapa de casetas')->assertDontSee('Abrir ficha e historial');
        $this->getJson('/casetas/puntos')->assertOk()->assertJsonPath('points.0.number','ALCANCE-01');
        $this->get('/perfiles')->assertOk();
    }

    public function test_direct_dossier_and_payment_requests_are_blocked_even_for_supervisors(): void
    {
        $caseta=$this->caseta();
        foreach (['/recaudaciones','/recaudaciones/plantilla','/casetas/documentos-historicos','/certificate/history/1'] as $url) {
            $this->get($url)->assertNotFound();
        }
        foreach (['/recaudaciones/revisar','/recaudaciones/importar','/casetas/documentos-historicos/vincular',
            '/casetas/'.$caseta->id.'/vincular-certificado','/casetas/'.$caseta->id.'/vincular-aviso'] as $url) {
            $this->post($url,[])->assertNotFound();
        }
        $license=$caseta->licenses()->create(['number'=>'L-01','year'=>2026,'issued_on'=>'2026-01-01','expires_on'=>'2026-12-31','source'=>'Registro de prueba']);
        $this->post('/casetas/'.$caseta->id.'/licencias/'.$license->id.'/verificar-pago',[])->assertNotFound();
        $this->assertDatabaseCount('license_payment_checks',0);
    }

    public function test_existing_reserved_data_is_kept_and_is_not_exposed_in_the_summary(): void
    {
        $caseta=$this->caseta();
        $license=$caseta->licenses()->create(['number'=>'L-01','year'=>2026,'issued_on'=>'2026-01-01','expires_on'=>'2026-12-31','source'=>'Registro de prueba']);
        DB::table('license_payment_checks')->insert(['caseta_license_id'=>$license->id,'status'=>'PAID','source'=>'Fuente anterior','reference'=>'Referencia anterior',
            'checked_at'=>'2026-01-01 12:00:00','created_at'=>'2026-01-01 12:00:00','fingerprint'=>str_repeat('a',64)]);
        $this->get('/casetas/'.$caseta->id)->assertOk()->assertSee('L-01')->assertDontSee('Pagado')->assertDontSee('Referencia anterior');
        $this->assertDatabaseCount('license_payment_checks',1);
    }

    public function test_basic_certificate_edit_does_not_require_history_or_create_new_revisions(): void
    {
        $payload=['type'=>'CASETA','mode'=>'NUEVO','solicitante'=>'Titular','ci'=>'DOC-1','rubro'=>'PRUEBA','direccion'=>'Dirección',
            'dim_ancho'=>1,'dim_largo'=>2,'tipo_via'=>'ACERA','dim_via'=>5,'ref_product'=>'VENTA DE DULCES','ref_turn'=>'DIURNO',
            'ref_start'=>'08:00','ref_end'=>'12:00','color'=>'BLANCO','material'=>'METAL','technical_proceed'=>0,'legal_proceed'=>0,
            'conclusions'=>'Observación de prueba','inspection_date'=>'23/10/2026','lat'=>-19.589,'lng'=>-65.754,
            'location_source'=>'MANUAL','location_reason'=>'Ubicación comprobada','location_confirmed'=>1];
        $this->post('/certificate/create',$payload)->assertSessionHasNoErrors();
        $certificate=Certificate::firstOrFail();
        $this->get('/certificate/edit/'.$certificate->id)->assertOk()->assertDontSee('Motivo de la corrección')->assertDontSee('Ver historial');
        $this->post('/certificate/edit/'.$certificate->id,$payload+['certificate_version'=>CertificateHistory::version($certificate)])->assertSessionHasNoErrors();
        $this->get('/certificate/show/'.$certificate->id)->assertOk()->assertDontSee('Historial de versiones');
        $certificate->refresh();
        $this->post('/certificate/edit/'.$certificate->id,array_replace($payload,[
            'legal_proceed'=>1,'certificate_version'=>CertificateHistory::version($certificate),
        ]))->assertSessionHasNoErrors();
        $certificate->refresh();
        $this->get('/certificate/technical/'.$certificate->id)->assertOk()->assertDontSee('Motivo de la corrección');
        $this->post('/certificate/technical/'.$certificate->id,$payload+[
            'certificate_version'=>CertificateHistory::version($certificate),
        ])->assertSessionHasNoErrors();
        $this->assertFalse($certificate->fresh()->technical_proceed);
        $this->assertNotNull($certificate->fresh()->inspection_date);
        $this->assertStringContainsString('Observación de prueba',$certificate->fresh()->conclusions);
        $this->assertDatabaseCount('certificate_revisions',0);
    }
    public function test_legacy_actions_respect_read_only_profiles_and_new_notice_links_to_the_right_module(): void
    {
        $user=auth()->user();
        $user->roles='ASESOR_LEGAL';
        $this->get('/notifications')->assertOk()->assertDontSee(route('notifications.create'), false);
        $this->get('/inspectors')->assertOk()->assertDontSee(route('inspectors.create'), false);
        $this->get('/admin')->assertOk()->assertDontSee(route('user-access.index'), false);
        $user->roles='JEFE_UNIDAD';
        $this->get('/notifications')->assertOk()->assertSee('Nuevo aviso')
            ->assertSee(route('notifications.create'), false)->assertDontSee(route('notices.create'), false);
    }
}
