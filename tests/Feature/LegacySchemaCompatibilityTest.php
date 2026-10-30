<?php
namespace Tests\Feature;

use App\User;
use App\Models\Certificate;
use App\Services\CertificateHistory;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacySchemaCompatibilityTest extends TestCase
{
    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->useEnvironmentPath(sys_get_temp_dir());
        $app->loadEnvironmentFrom('certtrafico-legacy-schema-no-env');
        $app->make(Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $paths = collect(glob(database_path('migrations/*.php')))->filter(fn ($p) => basename($p) < '2026_')
            ->map(fn ($p) => 'database/migrations/'.basename($p))->values()->all();
        $this->artisan('migrate', ['--path' => $paths, '--force' => true])->assertExitCode(0);
        $actor = new User(); $actor->id = 1; $actor->name = 'Prueba'; $actor->roles = 'JEFE_UNIDAD';
        $this->actingAs($actor);
    }

    public function test_original_documents_remain_readable_without_creating_caseta_tables(): void
    {
        $certificate = $this->certificate();
        $certificate->solicitante = 'Titular histórico'; $certificate->ci = '123';
        $certificate->direccion = 'Calle histórica'; $certificate->lat = -19.58; $certificate->lng = -65.75;
        $certificate->save();
        $before = DB::table('certificates')->get()->toJson();
        $this->get('/casetas')->assertOk()->assertSee('Titular histórico')->assertDontSee('Registrar caseta');
        $this->get('/casetas/mapa')->assertOk()->assertSee('documentos históricos')->assertDontSee('Registrar caseta');
        $this->getJson('/casetas/puntos')->assertOk()->assertJsonPath('total', 0)->assertJsonPath('legacy_total', 1)
            ->assertJsonPath('legacy_points.0.owner', 'Titular histórico');
        $this->assertFalse(Schema::hasTable('casetas'));
        $this->assertSame($before, DB::table('certificates')->get()->toJson());
    }

    public function test_unavailable_actions_are_guarded_before_database_model_binding(): void
    {
        foreach (['/casetas/nueva', '/casetas/1', '/casetas/1/editar', '/notice-photos/temporary/no-existe'] as $url) {
            $this->get($url)->assertStatus(503)->assertDontSee('SQLSTATE');
        }
        $this->postJson('/casetas', [])->assertStatus(503);
        $this->postJson('/notice-photos', [])->assertStatus(503);
        $this->getJson('/casetas/puntos?license=VIGENTE')->assertUnprocessable();
    }

    public function test_certificate_updates_only_write_existing_columns_even_with_history_flag_enabled(): void
    {
        config(['features.caseta_dossier' => true]);
        $certificate = $this->certificate(); $certificate->save();
        $version = CertificateHistory::version($certificate->fresh());
        $certificate->solicitante = 'Registro actualizado';
        $certificate->accuracy = 7; $certificate->location_source = 'GPS'; $certificate->located_at = now();
        CertificateHistory::save($certificate, $version);
        $this->assertSame('Registro actualizado', $certificate->fresh()->solicitante);
        $this->assertFalse(Schema::hasColumn('certificates', 'accuracy'));
        $this->assertFalse(Schema::hasTable('certificate_revisions'));
        $this->get('/certificate/history/'.$certificate->id)->assertStatus(503)->assertDontSee('SQLSTATE');
    }

    private function certificate(): Certificate
    {
        return (new Certificate())->forceFill(['tipo'=>'CASETA', 'solicitante'=>'Registro original', 'ci'=>'123', 'rubro'=>'VENTA',
            'direccion'=>'Calle', 'dim_ancho'=>1, 'dim_largo'=>1, 'color'=>'AZUL', 'material'=>'METAL', 'tipo_via'=>'ACERA',
            'dim_via'=>3, 'technical_proceed'=>false, 'legal_proceed'=>true, 'ref_product'=>'OTROS (DESCRIBIR)',
            'ref_turn'=>'DIA', 'ref_start'=>'08:00', 'ref_end'=>'12:00', 'lat'=>-19.58, 'lng'=>-65.75, 'conclusions'=>'Pendiente']);
    }

    public function test_legal_then_technical_workflow_and_parking_saves_use_the_original_schema(): void
    {
        $this->post('/certificate/create', ['type'=>'CASETA', 'mode'=>'NUEVO', 'solicitante'=>'Titular', 'ci'=>'777',
            'rubro'=>'VENTA', 'legal_proceed'=>1])->assertSessionHasNoErrors()->assertRedirect();
        $certificate = Certificate::firstOrFail();
        $this->get('/certificate/technical/'.$certificate->id)->assertOk()->assertSee('Confirmar este punto');
        $this->post('/certificate/technical/'.$certificate->id, ['certificate_version'=>CertificateHistory::version($certificate),
            'direccion'=>'Calle de prueba', 'dim_ancho'=>1, 'dim_largo'=>2, 'tipo_via'=>'ACERA', 'dim_via'=>4,
            'ref_product'=>'VENTA DE DULCES', 'ref_turn'=>'DIA', 'ref_start'=>'08:00', 'ref_end'=>'12:00',
            'color'=>'AZUL', 'material'=>'METAL', 'technical_proceed'=>1, 'inspection_date'=>now()->format('d/m/Y'),
            'lat'=>-19.58, 'lng'=>-65.75, 'location_source'=>'MANUAL', 'location_confirmed'=>1,
            'location_reason'=>'Punto comprobado en prueba'])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertNotNull($certificate->fresh()->inspection_date);
        foreach (['carparking', 'motoparking'] as $module) {
            $this->post('/'.$module.'/create', ['name'=>'Plaza', 'description'=>'Parqueo', 'zone'=>'CENTRO', 'quantity'=>10, 'free'=>5,
                'lat'=>-19.58, 'lng'=>-65.75, 'location_source'=>'MANUAL', 'location_confirmed'=>1,
                'location_reason'=>'Punto comprobado en prueba'])->assertSessionHasNoErrors()->assertRedirect();
            $this->assertDatabaseHas($module, ['name'=>'Plaza', 'lat'=>-19.58, 'quantity'=>10]);
            $this->assertFalse(Schema::hasColumn($module, 'location_source'));
        }
    }
}
