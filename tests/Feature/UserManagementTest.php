<?php
namespace Tests\Feature;

use App\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use \Tests\Concerns\IsolatedControlDatabase;

    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->useEnvironmentPath(sys_get_temp_dir());
        $app->loadEnvironmentFrom('certtrafico-user-management-no-env');
        $app->make(Kernel::class)->bootstrap();
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

    private function actor(string $role): User
    {
        $user = User::factory()->create(['roles'=>$role]);
        $this->actingAs($user);
        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return array_replace(['name'=>'Juana Pérez', 'ci'=>'1234567 LP', 'email'=>'juana@example.test', 'role'=>'INSPECTOR_NOTIFICADOR'], $overrides);
    }

    public function test_guests_and_non_supervisors_cannot_create_or_edit_accounts(): void
    {
        $this->get('/perfiles/crear')->assertRedirect('/login');
        $this->post('/perfiles', $this->payload())->assertRedirect('/login');
        foreach (['ASESOR_LEGAL', 'INSPECTOR_NOTIFICADOR', '', 'ADMINISTRATIVE'] as $role) {
            $actor = $this->actor($role);
            $this->get('/perfiles/crear')->assertForbidden();
            $this->post('/perfiles', $this->payload(['role'=>'ADMIN']))->assertForbidden();
            $this->get('/perfiles/'.$actor->id.'/editar')->assertForbidden();
            $this->post('/perfiles/'.$actor->id, ['role'=>'ADMIN'])->assertForbidden();
        }
        $this->assertDatabaseMissing('users', ['email'=>'juana@example.test']);
    }

    public function test_admin_and_jefatura_can_create_each_profile_from_three_personal_fields(): void
    {
        foreach (['ADMIN', 'JEFE_UNIDAD'] as $actorRole) {
            $this->actor($actorRole);
            foreach (['JEFE_UNIDAD','INSPECTOR_NOTIFICADOR','ASESOR_LEGAL'] as $index=>$role) {
                $suffix = $actorRole.'-'.$index;
                $response = $this->post('/perfiles', $this->payload(['ci'=>$suffix, 'email'=>strtolower($suffix).'@example.test', 'role'=>$role, 'username'=>'forged', 'password'=>'forged']));
                $response->assertSessionHasNoErrors()->assertRedirect('/perfiles');
                $account = User::where('ci',$suffix)->firstOrFail();
                $credentials = session('created_user_credentials');
                $this->assertSame($role, $account->roles);
                $this->assertSame($account->username, $credentials['username']);
                $this->assertNotSame('forged', $account->username);
                $this->assertGreaterThanOrEqual(12, strlen($credentials['password']));
                $this->assertTrue(Hash::check($credentials['password'], $account->password));
                $this->assertNotSame('forged', $credentials['password']);
                $this->assertArrayNotHasKey('password', $account->toArray());
            }
        }
        $this->assertSame(6, User::where('name','Juana Pérez')->count());
        $this->assertSame(6, User::where('name','Juana Pérez')->distinct()->count('username'));
    }

    public function test_required_identity_duplicate_email_and_invalid_roles_are_rejected(): void
    {
        $this->actor('ADMIN');
        $this->post('/perfiles', [])->assertSessionHasErrors(['name','ci','email','role']);
        $this->postJson('/perfiles', $this->payload(['name'=>['bad'],'ci'=>['bad'],'email'=>['bad']]))->assertUnprocessable()->assertJsonValidationErrors(['name','ci','email']);
        $this->post('/perfiles', $this->payload(['email'=>'not-an-email','role'=>'SUPERUSER']))->assertSessionHasErrors(['email','role']);
        $this->post('/perfiles', $this->payload())->assertSessionHasNoErrors();
        $this->post('/perfiles', $this->payload(['ci'=>'OTHER','email'=>'JUANA@EXAMPLE.TEST']))->assertSessionHasErrors('email');
        $this->post('/perfiles', $this->payload(['email'=>'another@example.test']))->assertSessionHasErrors('ci');
        $this->assertSame(2, User::count());
    }

    public function test_created_credentials_are_shown_once_and_the_account_can_log_in(): void
    {
        $this->actor('JEFE_UNIDAD');
        $this->post('/perfiles', $this->payload())->assertSessionHasNoErrors();
        $credentials = session('created_user_credentials');
        $this->get('/perfiles')->assertOk()->assertSee($credentials['username'])->assertSee($credentials['password']);
        $this->get('/perfiles')->assertOk()->assertDontSee($credentials['password']);
        $this->post('/logout');
        $this->post('/login', $credentials)->assertRedirect('/admin');
        $this->assertAuthenticatedAs(User::where('username',$credentials['username'])->firstOrFail());
        $this->get('/perfiles/crear')->assertForbidden();
    }

    public function test_existing_accounts_keep_password_and_last_supervisor_protection_when_edited(): void
    {
        $admin = $this->actor('ADMIN');
        $originalPassword = $admin->password;
        $this->assertNull($admin->ci);
        $this->get('/perfiles/'.$admin->id.'/editar')->assertOk();
        $this->post('/perfiles/'.$admin->id, $this->payload(['role'=>'ADMIN']))->assertSessionHasNoErrors();
        $this->assertSame($originalPassword, $admin->fresh()->password);
        $this->assertSame($admin->username, $admin->fresh()->username);
        $this->assertSame('juana@example.test', $admin->fresh()->email);
        $this->post('/perfiles/'.$admin->id, ['role'=>'ASESOR_LEGAL'])->assertSessionHasErrors('role');
        $this->assertSame('ADMIN',$admin->fresh()->roles);
    }
}
