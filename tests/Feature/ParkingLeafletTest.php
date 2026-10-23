<?php
namespace Tests\Feature;

use App\User;
use Carbon\Carbon;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ParkingLeafletTest extends TestCase
{
    use \Tests\Concerns\IsolatedControlDatabase;
    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->useEnvironmentPath(sys_get_temp_dir());
        $app->loadEnvironmentFrom('certtrafico-parking-leaflet-tests-no-env');
        $app->make(Kernel::class)->bootstrap();
        return $app;
    }
    protected function setUp(): void
    {
        parent::setUp();
        $this->configureControlDatabase();
        $this->artisan('migrate',['--force'=>true])->assertExitCode(0);
        Carbon::setTestNow(Carbon::parse('2026-10-23 16:00:00','UTC'));
    }
    protected function tearDown(): void { Carbon::setTestNow(); $this->releaseControlDatabase(); parent::tearDown(); }
    private function login(string $role = 'INSPECTOR_NOTIFICADOR'): void
    {
        $user=new User(); $user->id=1; $user->name='Inspector de prueba'; $user->roles=$role; $this->actingAs($user);
    }
    private function payload(array $changes=[]): array
    {
        return array_replace(['name'=>'Parqueo de prueba','description'=>'Calle de prueba','zone'=>'Centro','quantity'=>10,'free'=>3,
            'lat'=>-19.589,'lng'=>-65.754,'location_source'=>'MANUAL','location_reason'=>'Punto comprobado frente al parqueo','location_confirmed'=>1],$changes);
    }
    public function test_public_map_uses_leaflet_for_vehicles_motos_and_local_perimeter_without_a_session(): void
    {
        $response=$this->get('/')->assertOk()->assertSee('PUNTOS DE PARQUEO')
            ->assertSee('plugins/leaflet/leaflet.js',false)->assertSee('maps/restriccion-vehicular.geojson',false)
            ->assertSee('data-user-location',false)->assertDontSee('maps.googleapis.com',false)->assertDontSee('google.com/maps',false);
        $this->get('/public/parking')->assertOk()->assertSee('public/carpoints',false)->assertSee('public/motopoints',false);
    }
    public function test_vehicle_and_moto_forms_share_leaflet_and_do_not_start_with_fake_coordinates(): void
    {
        $this->login();
        foreach (['carparking','motoparking'] as $prefix) {
            $this->get('/'.$prefix)->assertOk()->assertSee('data-parking-canvas',false)->assertDontSee('google.maps',false);
            $this->get('/'.$prefix.'/create')->assertOk()->assertSee('data-gps',false)
                ->assertViewHas('model',fn($model)=>$model->lat===null && $model->lng===null);
            $this->post('/'.$prefix.'/create',$this->payload())->assertSessionHasNoErrors()->assertRedirect(route($prefix.'.index'));
            $this->get('/'.$prefix.'/edit/1')->assertOk()->assertSee('data-confirm-location',false);
            $this->assertDatabaseHas($prefix,['id'=>1,'lat'=>-19.589,'location_source'=>'MANUAL']);
        }
    }
    public function test_parking_rejects_unconfirmed_invalid_or_unjustified_points(): void
    {
        $this->login();
        foreach (['carparking','motoparking'] as $prefix) {
            foreach ([['lat'=>91],['lng'=>181],['location_confirmed'=>0],['location_source'=>'EXISTING'],['location_reason'=>'']] as $invalid) {
                $this->post('/'.$prefix.'/create',$this->payload($invalid))->assertSessionHasErrors();
            }
            $this->assertDatabaseCount($prefix,0);
        }
    }
    public function test_gps_precision_is_saved_and_public_endpoints_keep_only_public_data(): void
    {
        $this->login();
        foreach (['carparking'=>'carpoints','motoparking'=>'motopoints'] as $prefix=>$public) {
            $this->post('/'.$prefix.'/create',$this->payload(['location_source'=>'GPS','accuracy'=>7.5,'located_at'=>now('UTC')->toIso8601String()]))->assertSessionHasNoErrors();
            $this->assertDatabaseHas($prefix,['accuracy'=>7.5,'located_at'=>'2026-10-23 16:00:00']);
            $this->getJson('/public/'.$public)->assertOk()->assertJsonPath('0.name','Parqueo de prueba')
                ->assertJsonPath('0.lat',-19.589)->assertJsonMissingPath('0.accuracy')->assertJsonMissingPath('0.location_reason')->assertJsonMissingPath('0.located_at');
            $this->post('/'.$prefix.'/edit/1',$this->payload(['location_source'=>'EXISTING']))->assertSessionHasNoErrors();
            $this->assertDatabaseHas($prefix,['accuracy'=>7.5]);
            $this->post('/'.$prefix.'/edit/1',$this->payload(['location_source'=>'EXISTING','lat'=>-19.6]))->assertSessionHasErrors('location_source');
            $this->post('/'.$prefix.'/edit/1',$this->payload(['lat'=>-19.6]))->assertSessionHasNoErrors();
            $this->assertDatabaseHas($prefix,['lat'=>-19.6,'accuracy'=>null,'location_source'=>'MANUAL']);
        }
    }
    public function test_stale_gps_and_invalid_capacity_are_rejected_and_legal_cannot_modify_locations(): void
    {
        $this->login();
        $this->post('/carparking/create',$this->payload(['location_source'=>'GPS','accuracy'=>8,'located_at'=>now()->subMinutes(11)->toIso8601String()]))->assertSessionHasErrors('located_at');
        $this->post('/carparking/create',$this->payload(['free'=>11]))->assertSessionHasErrors('free');
        $this->login('ASESOR_LEGAL');
        foreach (['carparking','motoparking'] as $prefix) {
            $this->get('/'.$prefix)->assertOk()->assertDontSee('Nuevo parqueo');
            $this->post('/'.$prefix.'/create',$this->payload())->assertForbidden();
            $this->post('/'.$prefix.'/edit/1',$this->payload())->assertForbidden();
        }
    }
}
