<?php
namespace Tests\Feature;

use App\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportPaginationTest extends TestCase
{
    use \Tests\Concerns\IsolatedControlDatabase;

    public function createApplication()
    {
        $app=require __DIR__.'/../../bootstrap/app.php';
        $app->useEnvironmentPath(sys_get_temp_dir());
        $app->loadEnvironmentFrom('certtrafico-report-pagination-no-env');
        $app->make(Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureControlDatabase();
        $paths=collect(glob(database_path('migrations/*.php')))->filter(fn($path)=>basename($path)<'2026_')
            ->map(fn($path)=>'database/migrations/'.basename($path))->values()->all();
        $this->artisan('migrate',['--path'=>$paths,'--force'=>true])->assertExitCode(0);
    }

    protected function tearDown(): void
    {
        $this->releaseControlDatabase();
        parent::tearDown();
    }

    private function login(): void
    {
        $user=new User(); $user->id=1; $user->name='Operador de prueba'; $user->username='prueba'; $user->roles='JEFE_UNIDAD';
        $this->actingAs($user);
    }

    private function certificate(string $name,string $date): void
    {
        DB::table('certificates')->insert(['tipo'=>'CASETA','solicitante'=>$name,'inspection_date'=>$date,'ci'=>'PRUEBA','rubro'=>'Prueba','direccion'=>'Prueba','dim_ancho'=>1,'dim_largo'=>1,'color'=>'BLANCO','material'=>'METAL','tipo_via'=>'ACERA','dim_via'=>4,'technical_proceed'=>true,'legal_proceed'=>true,'ref_product'=>'Prueba','ref_turn'=>'DIA','ref_start'=>'08:00','ref_end'=>'12:00','lat'=>-19.589,'lng'=>-65.754,'conclusions'=>'Prueba']);
    }

    public function test_report_requires_login(): void
    {
        $this->get('/reports/shops')->assertRedirect(route('login'));
    }

    public function test_report_uses_thirty_rows_per_page_and_continuous_numbering_without_changing_records(): void
    {
        for($i=1;$i<=65;$i++) $this->certificate(sprintf('REGISTRO-%03d',$i),'2026-10-01');
        $before=DB::table('certificates')->orderBy('id')->get()->toJson();
        $this->login();
        $first=$this->get('/reports/shops')->assertOk();
        $data=$first->viewData('data');
        $this->assertInstanceOf(LengthAwarePaginator::class,$data);
        $this->assertSame(30,$data->count()); $this->assertSame(65,$data->total());
        $first->assertSee('REGISTRO-001')->assertSee('REGISTRO-030')->assertDontSee('REGISTRO-031');
        $second=$this->get('/reports/shops?page=2')->assertOk();
        $second->assertSee('REGISTRO-031')->assertSee('REGISTRO-060')->assertDontSee('REGISTRO-061');
        $this->assertSame(31,$second->viewData('data')->firstItem());
        $this->assertMatchesRegularExpression('/<td>\s*31\s*<\/td>/',$second->getContent());
        $last=$this->get('/reports/shops?page=3')->assertOk();
        $this->assertSame(5,$last->viewData('data')->count());
        $this->assertSame($before,DB::table('certificates')->orderBy('id')->get()->toJson());
    }

    public function test_date_filters_include_both_boundaries_and_survive_pagination(): void
    {
        for($i=1;$i<=31;$i++) $this->certificate(sprintf('INICIO-%03d',$i),'2026-10-01');
        $this->certificate('FIN-INCLUIDO','2026-10-29');
        $this->certificate('FUERA-ANTES','2026-09-30'); $this->certificate('FUERA-DESPUES','2026-10-30');
        $this->login();
        $first=$this->get('/reports/shops?start=2026-10-01&end=2026-10-29')->assertOk();
        $data=$first->viewData('data');
        $this->assertInstanceOf(LengthAwarePaginator::class,$data);
        $this->assertSame(32,$data->total());
        parse_str(parse_url($data->nextPageUrl(),PHP_URL_QUERY),$query);
        $this->assertSame('2026-10-01',$query['start']);$this->assertSame('2026-10-29',$query['end']);
        $first->assertSee($data->nextPageUrl());
        $this->get('/reports/shops?start=2026-10-01&end=2026-10-29&page=2')->assertOk()
            ->assertSee('INICIO-031')->assertSee('FIN-INCLUIDO')->assertDontSee('FUERA-ANTES')->assertDontSee('FUERA-DESPUES');
    }

    public function test_invalid_dates_and_reversed_ranges_are_rejected(): void
    {
        $this->login();
        $this->get('/reports/shops?start=no-es-fecha')->assertSessionHasErrors('start');
        $this->get('/reports/shops?end=2026-02-30')->assertSessionHasErrors('end');
        $this->get('/reports/shops?start=2026-10-29&end=2026-10-01')->assertSessionHasErrors('end');
    }
}
