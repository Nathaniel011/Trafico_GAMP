<?php
namespace Tests\Feature;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UpgradeDataPreservationTest extends TestCase
{
    use \Tests\Concerns\IsolatedControlDatabase;

    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->useEnvironmentPath(sys_get_temp_dir());
        $app->loadEnvironmentFrom('certtrafico-upgrade-preservation-no-env');
        $app->make(Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureControlDatabase();
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        $this->releaseControlDatabase();
        parent::tearDown();
    }

    public function test_upgrade_preserves_existing_documents_users_tokens_and_photographs(): void
    {
        $paths = collect(glob(database_path('migrations/*.php')))->filter(fn ($path) => basename($path) < '2026_10_23')
            ->map(fn ($path) => 'database/migrations/'.basename($path))->values()->all();
        $this->artisan('migrate', ['--path'=>$paths, '--force'=>true])->assertExitCode(0);
        DB::table('users')->insert(['name'=>'Cuenta histórica de prueba','username'=>'historico','password'=>bcrypt('clave-ficticia'),'roles'=>'ADMIN','created_at'=>'2024-01-01 10:00:00']);
        DB::table('certificates')->insert(['tipo'=>'CASETA','modalidad'=>'NUEVO','solicitante'=>'Registro histórico de prueba','ci'=>'PRUEBA-ANTIGUA','rubro'=>'PRUEBA','direccion'=>'Dirección histórica','dim_ancho'=>1,'dim_largo'=>2,'color'=>'BLANCO','material'=>'METAL','tipo_via'=>'ACERA','dim_via'=>5,'technical_proceed'=>false,'legal_proceed'=>true,'ref_product'=>'PRUEBA','ref_turn'=>'DIURNO','ref_start'=>'08:00:00','ref_end'=>'12:00:00','lat'=>-19.589,'lng'=>-65.754,'conclusions'=>'Conclusión histórica intacta','inspection_date'=>'2024-01-01','code'=>'00000000-0000-4000-8000-000000000123']);
        DB::table('notices')->insert(['type'=>'SHOPS','number'=>'000123','address'=>'Dirección histórica','lat'=>-19.589,'lng'=>-65.754,'notice_date'=>'2024-01-01 10:00:00','owner'=>'Titular histórico','document'=>'PRUEBA-ANTIGUA','obs'=>'Observación original','retirement'=>null]);
        DB::table('personal_access_tokens')->insert(['tokenable_type'=>'App\\Models\\Inspector','tokenable_id'=>1,'name'=>'token histórico','token'=>hash('sha256','token-ficticio'),'abilities'=>'["*"]']);
        Storage::disk('public')->put('files/historica.png', 'contenido-ficticio-anterior');
        DB::table('uploaded_files')->insert(['name'=>'historica.png','path'=>'files/historica.png','url'=>'/storage/files/historica.png','extension'=>'png','size'=>26,'entity_type'=>'NOTICE','entity_id'=>1]);
        $before=[];
        foreach (['users','certificates','notices','personal_access_tokens','uploaded_files'] as $table) {
            $before[$table]=(array) DB::table($table)->first();
        }
        $this->artisan('migrate', ['--force'=>true])->assertExitCode(0);
        $this->artisan('migrate', ['--force'=>true])->assertExitCode(0);
        foreach ($before as $table=>$row) {
            $this->assertDatabaseCount($table,1);
            $after=(array) DB::table($table)->first();
            foreach ($row as $column=>$value) { $this->assertSame($value,$after[$column], $table.'.'.$column); }
        }
        $this->assertSame('contenido-ficticio-anterior',Storage::disk('public')->get('files/historica.png'));
        $this->assertDatabaseCount('casetas',0);
        $this->assertDatabaseCount('certificate_revisions',0);
        $notice=DB::table('notices')->first();
        $this->assertNull($notice->caseta_id);
        $this->assertNull($notice->sequence);
        $this->assertNull($notice->deadline_at);
    }
}
