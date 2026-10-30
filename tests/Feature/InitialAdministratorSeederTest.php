<?php
namespace Tests\Feature;

use App\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InitialAdministratorSeederTest extends TestCase
{
    use \Tests\Concerns\IsolatedControlDatabase;

    public function createApplication()
    {
        $app=require __DIR__.'/../../bootstrap/app.php';
        $app->useEnvironmentPath(sys_get_temp_dir());
        $app->loadEnvironmentFrom('certtrafico-initial-admin-no-env');
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

    public function test_installation_seeds_only_the_requested_administrator_and_no_sample_accounts_or_records(): void
    {
        $this->artisan('db:seed',['--force'=>true])->assertExitCode(0);
        $this->assertDatabaseCount('users',1);
        $admin=User::firstOrFail();
        $this->assertSame('administrador',$admin->username);
        $this->assertSame('Administrador',$admin->name);
        $this->assertSame('ADMIN',$admin->roles);
        $this->assertTrue(Hash::check('administrador',$admin->password));
        $this->assertNotSame('administrador',$admin->password);
        foreach (['inspectors','groups','transport_lines','casetas','notices','certificates'] as $table) $this->assertDatabaseCount($table,0);
        $this->post('/login',['username'=>'administrador','password'=>'administrador'])->assertRedirect('/admin');
        $this->get('/perfiles')->assertOk();
    }

    public function test_repeating_the_seeder_keeps_the_current_password_and_does_not_duplicate_accounts(): void
    {
        $this->artisan('db:seed',['--force'=>true])->assertExitCode(0);
        $admin=User::firstOrFail();
        $admin->password=Hash::make('UnaClaveNueva-123');
        $admin->save();
        $newHash=$admin->password;
        $this->artisan('db:seed',['--force'=>true])->assertExitCode(0);
        $this->assertDatabaseCount('users',1);
        $this->assertSame($newHash,$admin->fresh()->password);
    }

    public function test_existing_accounts_and_roles_are_not_replaced_or_reset_on_server_updates(): void
    {
        $admin=User::factory()->create(['roles'=>'ADMIN','username'=>'admin.existente']);
        $inspector=User::factory()->create(['roles'=>'INSPECTOR_NOTIFICADOR']);
        $before=User::orderBy('id')->get()->map->getAttributes()->all();
        $this->artisan('db:seed',['--force'=>true])->assertExitCode(0);
        $this->assertSame($before,User::orderBy('id')->get()->map->getAttributes()->all());
        $this->assertDatabaseMissing('users',['username'=>'administrador']);
        $this->assertSame('ADMIN',$admin->fresh()->roles);
        $this->assertSame('INSPECTOR_NOTIFICADOR',$inspector->fresh()->roles);
    }
}
