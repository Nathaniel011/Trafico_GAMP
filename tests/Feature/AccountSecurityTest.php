<?php
namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AccountSecurityTest extends \Tests\TestCase
{
    use \Tests\Concerns\IsolatedControlDatabase;

    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->useEnvironmentPath(sys_get_temp_dir());
        $app->loadEnvironmentFrom('certtrafico-user-management-no-env');
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureControlDatabase();
        $this->artisan('migrate', ['--force'=>true])->assertExitCode(0);
    }

    protected function tearDown(): void
    {
        $this->releaseControlDatabase();
        parent::tearDown();
    }

    private function loginAccount(string $role): User
    {
        $user=User::factory()->create(['roles'=>$role]);
        $this->withSession(['url.intended'=>'/admin']);
        $this->post('/login',['username'=>$user->username,'password'=>'password'])->assertRedirect('/admin');
        return $user->refresh();
    }

    public function test_second_administrator_is_rejected_in_creation_editing_and_console(): void
    {
        $admin=$this->loginAccount('ADMIN');
        $chief=User::factory()->create(['roles'=>'JEFE_UNIDAD']);
        $this->get('/perfiles/crear')->assertOk()->assertDontSee('value="ADMIN"',false);
        $this->post('/perfiles',['name'=>'Otro admin','ci'=>'ADMIN-02','email'=>'otro@example.test','role'=>'ADMIN'])->assertSessionHasErrors('role');
        $this->post('/perfiles/'.$chief->id,['role'=>'ADMIN'])->assertSessionHasErrors('role');
        $this->assertSame('JEFE_UNIDAD',$chief->fresh()->roles);
        $this->post('/perfiles/'.$admin->id,['role'=>'JEFE_UNIDAD'])->assertSessionHasErrors('role');
        $this->assertSame('ADMIN',$admin->fresh()->roles);
        $this->artisan('create:user',['--role'=>'ADMIN'])->expectsOutputToContain('Ya existe un Administrador')->assertExitCode(1);
    }

    public function test_chief_can_assign_first_admin_and_legacy_multiple_admins_can_be_reconciled(): void
    {
        $this->loginAccount('JEFE_UNIDAD');
        $this->post('/perfiles',['name'=>'Primer admin','ci'=>'ADMIN-01','email'=>'primero@example.test','role'=>'ADMIN'])->assertSessionHasNoErrors();
        $admin=User::where('roles','ADMIN')->firstOrFail();
        DB::table('users')->insert(['name'=>'Admin antiguo','username'=>'antiguo','roles'=>'CONSULTA,ADMIN','password'=>Hash::make('password')]);
        $legacy=User::where('username','antiguo')->firstOrFail();
        $this->post('/perfiles',['name'=>'Tercero','ci'=>'ADMIN-03','email'=>'tercero@example.test','role'=>'ADMIN'])->assertSessionHasErrors('role');
        $this->post('/perfiles/'.$legacy->id,['role'=>'JEFE_UNIDAD'])->assertSessionHasNoErrors();
        $this->assertSame('ADMIN',$admin->fresh()->roles);
        $this->post('/perfiles/'.$admin->id,['role'=>'ASESOR_LEGAL'])->assertSessionHasErrors('role');
    }

    public function test_only_supervisors_can_reset_non_admin_passwords_without_a_current_password(): void
    {
        foreach (['ADMIN','JEFE_UNIDAD'] as $role) {
            $actor=$this->loginAccount($role);
            foreach (['INSPECTOR_NOTIFICADOR','ASESOR_LEGAL','JEFE_UNIDAD'] as $targetRole) {
            $target=User::factory()->create(['roles'=>$targetRole,'remember_token'=>'previous-token']);
            $oldHash=$target->password;
            $this->get('/perfiles/'.$target->id.'/restablecer-contrasena')->assertOk()->assertDontSee('name="current_password"',false);
            $this->post('/perfiles/'.$target->id.'/restablecer-contrasena',[])->assertSessionHasNoErrors()->assertRedirect('/perfiles');
            $this->assertNotSame($oldHash,$target->fresh()->password);
            $credentials=session('created_user_credentials');
            $this->assertSame($target->username,$credentials['username']);
            $this->assertTrue(Hash::check($credentials['password'],$target->fresh()->password));
            $this->assertNotSame('previous-token',$target->fresh()->remember_token);
            $this->assertSame($actor->password,$actor->fresh()->password);
            $this->get('/perfiles')->assertOk()->assertSee('Contraseña restablecida')->assertSee($credentials['password']);
            $this->get('/perfiles')->assertDontSee($credentials['password']);
            }
            $this->post('/logout');
        }
        foreach (['INSPECTOR_NOTIFICADOR','ASESOR_LEGAL'] as $role) {
            $this->loginAccount($role);
            $target=User::where('roles','ADMIN')->firstOrFail();
            $this->get('/perfiles/'.$target->id.'/restablecer-contrasena')->assertForbidden();
            $this->post('/perfiles/'.$target->id.'/restablecer-contrasena',['current_password'=>'password'])->assertForbidden();
            $this->post('/logout');
        }
    }

    public function test_resetting_the_administrator_still_requires_the_operators_current_password(): void
    {
        $actor=$this->loginAccount('JEFE_UNIDAD');
        $admin=User::factory()->create(['roles'=>'ADMIN']);
        $oldHash=$admin->password;
        $url='/perfiles/'.$admin->id.'/restablecer-contrasena';
        $this->get($url)->assertOk()->assertSee('name="current_password"',false);
        $this->post($url,[])->assertSessionHasErrors('current_password');
        $this->post($url,['current_password'=>'incorrecta'])->assertSessionHasErrors('current_password');
        $this->post($url,['role'=>'JEFE_UNIDAD'])->assertSessionHasErrors('current_password');
        $this->assertSame($oldHash,$admin->fresh()->password);
        $this->post($url,['current_password'=>'password'])->assertSessionHasNoErrors()->assertRedirect('/perfiles');
        $this->assertTrue(Hash::check(session('created_user_credentials.password'),$admin->fresh()->password));
        $this->assertSame($actor->password,$actor->fresh()->password);
        $admin->roles='CONSULTA,ADMIN'; $admin->save();
        $this->post($url,[])->assertSessionHasErrors('current_password');
    }
    public function test_every_profile_can_change_only_their_own_password_after_verifying_the_current_one(): void
    {
        foreach (['ADMIN','JEFE_UNIDAD','INSPECTOR_NOTIFICADOR','ASESOR_LEGAL'] as $role) {
            $user=$this->loginAccount($role);
            $other=User::factory()->create(['roles'=>'ASESOR_LEGAL']);
            $otherHash=$other->password;
            $this->get('/mi-cuenta/contrasena')->assertOk()->assertSee('Contraseña actual');
            $payload=['current_password'=>'password','password'=>'NuevaClave-123','password_confirmation'=>'NuevaClave-123','user_id'=>$other->id];
            $this->post('/mi-cuenta/contrasena',array_replace($payload,['current_password'=>'incorrecta']))->assertSessionHasErrors('current_password');
            $this->post('/mi-cuenta/contrasena',array_replace($payload,['password_confirmation'=>'diferente']))->assertSessionHasErrors('password');
            $this->post('/mi-cuenta/contrasena',$payload)->assertSessionHasNoErrors()->assertRedirect('/mi-cuenta/contrasena');
            $this->assertTrue(Hash::check('NuevaClave-123',$user->fresh()->password));
            $this->assertSame($otherHash,$other->fresh()->password);
            $this->get('/admin')->assertOk();
            $this->post('/logout');
            $this->post('/login',['username'=>$user->username,'password'=>'password'])->assertSessionHasErrors('username');
            $this->post('/login',['username'=>$user->username,'password'=>'NuevaClave-123'])->assertRedirect('/admin');
            $this->post('/logout');
        }
    }

    public function test_password_fields_are_not_flashed_and_guests_cannot_reach_account_actions(): void
    {
        $this->get('/mi-cuenta/contrasena')->assertRedirect('/login');
        $this->post('/mi-cuenta/contrasena',[])->assertRedirect('/login');
        $this->get('/perfiles/1/restablecer-contrasena')->assertRedirect('/login');
        $this->post('/perfiles/1/restablecer-contrasena',[])->assertRedirect('/login');
        $user=$this->loginAccount('ADMIN');
        $this->post('/mi-cuenta/contrasena',['current_password'=>'secreto-erroneo','password'=>'corta','password_confirmation'=>'corta'])->assertSessionHasErrors();
        $old=session('_old_input',[]);
        foreach (['current_password','password','password_confirmation'] as $key) $this->assertArrayNotHasKey($key,$old);
        $this->get('/perfiles/'.$user->id.'/restablecer-contrasena')->assertRedirect('/mi-cuenta/contrasena');
        $this->post('/perfiles/'.$user->id.'/restablecer-contrasena',['current_password'=>'password'])->assertSessionHasErrors('user');
    }

    public function test_sessions_with_an_old_password_hash_are_rejected_after_a_reset(): void
    {
        $user=$this->loginAccount('INSPECTOR_NOTIFICADOR');
        $oldHash=$user->password;
        $this->get('/admin')->assertOk();
        User::whereKey($user->id)->update(['password'=>Hash::make('Reemplazada-123')]);
        auth()->forgetGuards();
        $this->withSession(['login_web_'.sha1(\Illuminate\Auth\SessionGuard::class)=>$user->id,'password_hash_web'=>$oldHash]);
        $this->get('/admin')->assertRedirect('/login');
        $this->assertGuest();
    }
}
