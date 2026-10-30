<?php
namespace Tests\Feature;

use App\User;
use App\Notice;
use App\Models\Caseta;
use App\Models\Certificate;
use App\Models\Complaint;
use Illuminate\Contracts\Console\Kernel;
use Tests\TestCase;

class RoleVisibilityTest extends TestCase
{
    use \Tests\Concerns\IsolatedControlDatabase;

    public function createApplication()
    {
        $app=require __DIR__.'/../../bootstrap/app.php';
        $app->useEnvironmentPath(sys_get_temp_dir());
        $app->loadEnvironmentFrom('certtrafico-role-visibility-no-env');
        $app->make(Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureControlDatabase();
        $this->artisan('migrate',['--force'=>true])->assertExitCode(0);
    }

    protected function tearDown(): void
    {
        $this->releaseControlDatabase();
        parent::tearDown();
    }

    private function actor(string $role): void
    {
        $user=new User(); $user->id=1; $user->name='Operador de prueba'; $user->roles=$role;
        $this->actingAs($user);
    }

    private function caseta(): Caseta
    {
        return Caseta::create(['number'=>'ROL-01','owner'=>'Titular de prueba','document'=>'DOC-ROL','address'=>'Dirección de prueba',
            'lat'=>-19.589,'lng'=>-65.754,'location_source'=>'MANUAL']);
    }

    private function complaint(): Complaint
    {
        $model=new Complaint();
        $model->forceFill(['vehicle_plaque'=>'PRUEBA','route_code'=>'01','state'=>'PENDIENTE','notes'=>'Solo consulta'])->save();
        return $model;
    }

    public function test_inspector_cannot_start_or_edit_the_legal_stage_but_can_open_the_technical_stage(): void
    {
        $this->actor('JEFE_UNIDAD');
        $this->post('/certificate/create',['type'=>'CASETA','mode'=>'NUEVO','solicitante'=>'Titular','ci'=>'DOC-ROL','rubro'=>'VENTA','legal_proceed'=>1])->assertSessionHasNoErrors();
        $certificate=Certificate::firstOrFail();
        $caseta=$this->caseta();
        $this->actor('INSPECTOR_NOTIFICADOR');
        $this->get('/certificate')->assertOk()->assertDontSee(route('certificate.create'),false);
        $this->get('/admin')->assertOk()->assertDontSee(route('certificate.create'),false)->assertSee(route('casetas.create'),false);
        $this->get('/casetas/'.$caseta->id)->assertOk()->assertDontSee(route('certificate.create',['caseta_id'=>$caseta->id]),false)->assertSee(route('notices.create',['caseta_id'=>$caseta->id,'mode'=>'SHOP']));
        $this->get('/certificate/create')->assertForbidden();
        $this->post('/certificate/create',[])->assertForbidden();
        $this->get('/certificate/edit/'.$certificate->id)->assertForbidden();
        $this->post('/certificate/edit/'.$certificate->id,[])->assertForbidden();
        $this->get('/certificate/technical/'.$certificate->id)->assertOk();
        $actions=$this->getJson('/certificate/data?draw=1&start=0&length=10')->assertOk()->json('data.0.actions');
        $this->assertStringNotContainsString('Editar legal',$actions);
        $this->assertStringContainsString('Completar técnico',$actions);
        $this->assertDatabaseCount('certificates',1);
    }

    public function test_consultation_profiles_see_values_without_modification_controls_or_post_actions(): void
    {
        $complaint=$this->complaint();
        $notice=new Notice();
        $notice->forceFill(['type'=>'SHOP','number'=>'0001','address'=>'Dirección','lat'=>-19.589,'lng'=>-65.754,'owner'=>'Titular de prueba','document'=>'DOC-01','phone'=>'123456','obs'=>'Observación de consulta','notice_date'=>now(),'retirement'=>null])->save();
        foreach (['ASESOR_LEGAL',''] as $role) {
            $this->actor($role);
            $this->get('/complaints/'.$complaint->id)->assertOk()->assertSee('PENDIENTE')->assertDontSee('id="btn-accepted"',false)->assertDontSee('id="btn-declined"',false)->assertDontSee(route('complaints.state',$complaint->id),false);
            $this->post('/complaints/'.$complaint->id.'/state',['state'=>'ACEPTADO'])->assertForbidden();
            $this->get('/notices/'.$notice->id)->assertOk()->assertSee('Titular de prueba')->assertSee('Observación de consulta')->assertDontSee('action="'.route('notices.update',$notice->id).'"',false)->assertDontSee('Guardar actualización');
            $this->post('/notices/'.$notice->id.'/edit',[])->assertForbidden();
            foreach (['carparking','motoparking'] as $prefix) {
                $this->get('/'.$prefix)->assertOk()->assertDontSee(route($prefix.'.create'),false);
                $this->get('/'.$prefix.'/create')->assertForbidden();
                $this->post('/'.$prefix.'/create',[])->assertForbidden();
            }
            $this->get('/admin')->assertOk()->assertDontSee(route('user-access.index'),false)->assertSee(route('account-password.edit'),false);
            $this->get('/perfiles')->assertForbidden();
        }
        $this->assertSame('PENDIENTE',$complaint->fresh()->state);
    }

    public function test_supervisors_keep_legal_registration_and_field_profiles_keep_their_actions(): void
    {
        $complaint=$this->complaint();
        foreach (['ADMIN','JEFE_UNIDAD','INSPECTOR_NOTIFICADOR'] as $role) {
            $this->actor($role);
            $this->get('/complaints/'.$complaint->id)->assertOk()->assertSee('id="btn-accepted"',false)->assertSee(route('complaints.state',$complaint->id),false);
            $this->get('/carparking')->assertOk()->assertSee(route('carparking.create'),false);
            if ($role !== 'INSPECTOR_NOTIFICADOR') {
                $this->get('/certificate/create')->assertOk();
                $this->get('/certificate')->assertOk()->assertSee(route('certificate.create'),false);
                $this->get('/admin')->assertOk()->assertSee(route('certificate.create'),false)->assertSee(route('user-access.index'),false);
            }
        }
    }
}
