<?php
namespace Tests\Feature;

use App\User;
use App\Models\Inspector;
use App\Models\Complaint;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SystemRegressionTest extends TestCase
{
    use \Tests\Concerns\IsolatedControlDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureControlDatabase();
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        Carbon::setTestNow(Carbon::parse('2026-10-29 12:00:00', 'America/La_Paz'));
        $user = new User();
        $user->id = 1; $user->name = 'Operador ficticio'; $user->roles = 'JEFE_UNIDAD';
        $this->actingAs($user);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        $this->releaseControlDatabase();
        parent::tearDown();
    }

    public function test_group_accepts_current_year_and_rejects_fractional_or_future_years(): void
    {
        $id = DB::table('groups')->insertGetId(['name' => 'Grupo de prueba']);
        $this->post('/groups/'.$id.'/edit', ['name' => 'Grupo de prueba', 'year' => 2026])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('groups', ['id' => $id, 'creation_date' => '2026-01-01']);
        foreach ([2027, 2025.5] as $year) {
            $this->post('/groups/'.$id.'/edit', ['name' => 'Grupo de prueba', 'year' => $year])->assertSessionHasErrors('year');
        }
    }

    public function test_group_foundation_date_can_be_cleared(): void
    {
        $id = DB::table('groups')->insertGetId(['name' => 'Grupo de prueba', 'creation_date' => '2020-01-01']);
        $this->post('/groups/'.$id.'/edit', ['name' => 'Grupo de prueba', 'year' => ''])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('groups', ['id' => $id, 'creation_date' => null]);
    }

    public function test_missing_records_return_not_found_instead_of_internal_error(): void
    {
        $this->get('/notifications/987654/edit')->assertNotFound();
        $this->post('/notifications/987654/edit', ['title' => 'Prueba', 'body' => 'Prueba', 'level' => 'INFO'])->assertNotFound();
        $this->post('/notifications/987654')->assertNotFound();
        $this->getJson('/api/routes/987654/path')->assertNotFound();
    }

    public function test_report_filters_by_inspection_date_and_includes_both_end_dates(): void
    {
        foreach (['2026-10-01' => 'INICIO PRUEBA', '2026-10-29' => 'FIN PRUEBA', '2026-09-30' => 'FUERA PRUEBA'] as $date => $name) {
            DB::table('certificates')->insert(['tipo'=>'CASETA', 'solicitante'=>$name, 'inspection_date'=>$date, 'ci'=>'PRUEBA', 'rubro'=>'Prueba', 'direccion'=>'Prueba', 'dim_ancho'=>1, 'dim_largo'=>1, 'color'=>'BLANCO', 'material'=>'METAL', 'tipo_via'=>'ACERA', 'dim_via'=>4, 'technical_proceed'=>false, 'legal_proceed'=>false, 'ref_product'=>'Prueba', 'ref_turn'=>'DIA', 'ref_start'=>'08:00', 'ref_end'=>'12:00', 'lat'=>-19.589, 'lng'=>-65.754, 'conclusions'=>'Prueba']);
        }
        $this->get('/reports/shops?start=2026-10-01&end=2026-10-29')->assertOk()
            ->assertSee('INICIO PRUEBA')->assertSee('FIN PRUEBA')->assertDontSee('FUERA PRUEBA');
        $this->get('/reports/shops')->assertOk()->assertSee('FUERA PRUEBA');
        $this->get('/reports/shops?start=2026-10-29&end=2026-10-01')->assertSessionHasErrors('end');
        $this->get('/reports/shops?start=no-es-fecha')->assertSessionHasErrors('start');
    }

    private function inspector(): void
    {
        DB::table('inspectors')->insert(['id' => 55, 'name' => 'Inspector ficticio', 'username' => 'inspector-ficticio', 'password' => bcrypt('password-ficticio'), 'enabled' => true]);
        Sanctum::actingAs(Inspector::findOrFail(55));
        Storage::fake('public');
    }

    public function test_two_complaint_photos_in_the_same_second_do_not_overwrite_each_other(): void
    {
        $this->inspector();
        $file = UploadedFile::fake()->image('foto.png');
        $photo = base64_encode($file->get());
        for ($i = 0; $i < 2; $i++) {
            $this->postJson('/api/complaints', ['plaque' => 'PRUEBA', 'transport_line' => 'PRUEBA', 'photo' => $photo])->assertOk();
        }
        $paths = Complaint::pluck('photo_path');
        $this->assertNotSame($paths[0], $paths[1]);
        foreach ($paths as $path) { Storage::disk('public')->assertExists($path); }
    }

    public function test_complaint_rejects_invalid_photo_without_creating_files_or_records(): void
    {
        $this->inspector();
        foreach (['no-es-base64', base64_encode('<script>no es imagen</script>')] as $photo) {
            $this->postJson('/api/complaints', ['plaque' => 'PRUEBA', 'transport_line' => 'PRUEBA', 'photo' => $photo])->assertUnprocessable()->assertJsonValidationErrors('photo');
        }
        $this->assertDatabaseCount('complaints', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }
}
