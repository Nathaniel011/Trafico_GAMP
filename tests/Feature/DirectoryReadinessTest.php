<?php
namespace Tests\Feature;

use App\Models\Group;
use App\Models\Inspector;
use App\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DirectoryReadinessTest extends TestCase
{
    public function createApplication()
    {
        $app=require __DIR__.'/../../bootstrap/app.php';
        $app->useEnvironmentPath(sys_get_temp_dir());
        $app->loadEnvironmentFrom('certtrafico-directory-old-schema-no-env');
        $app->make(Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);
        DB::purge('sqlite');
        $paths=collect(glob(database_path('migrations/*.php')))->filter(fn($path)=>basename($path)<'2026_')
            ->map(fn($path)=>'database/migrations/'.basename($path))->values()->all();
        $this->artisan('migrate',['--path'=>$paths,'--force'=>true])->assertExitCode(0);
        Storage::fake('public');
    }

    private function actor(string $role): void
    {
        $user=new User();$user->id=1;$user->name='Operador de prueba';$user->roles=$role;
        $this->actingAs($user);
    }

    public function test_inspector_details_render_with_the_old_schema_without_exposing_passwords(): void
    {
        $inspector=new Inspector();
        $inspector->name='<script>alert(1)</script>';$inspector->username='inspector.lectura';
        $inspector->password=Hash::make('ClaveFicticia-123');$inspector->enabled=true;$inspector->save();
        $this->get('/inspectors/'.$inspector->id)->assertRedirect(route('login'));
        $this->actor('ASESOR_LEGAL');
        $this->get('/inspectors/'.$inspector->id)->assertOk()->assertSee('inspector.lectura')->assertSee('Activo')
            ->assertSee($inspector->name)->assertDontSee($inspector->name,false)
            ->assertDontSee($inspector->password,false)->assertDontSee('ClaveFicticia-123')
            ->assertDontSee(route('inspectors.edit',$inspector->id),false);
        $this->get('/inspectors/999')->assertNotFound();
        $this->actor('JEFE_UNIDAD');
        $this->get('/inspectors/'.$inspector->id)->assertOk()->assertSee(route('inspectors.edit',$inspector->id),false);
        $this->assertSame(1,Inspector::count());
    }

    public function test_only_admin_and_chief_can_open_and_create_groups(): void
    {
        $this->get('/groups/create')->assertRedirect(route('login'));
        $this->post('/groups/create',[])->assertRedirect(route('login'));
        foreach(['ASESOR_LEGAL','INSPECTOR_NOTIFICADOR',''] as $role) {
            $this->actor($role);
            $this->get('/groups')->assertOk()->assertDontSee(route('groups.create'),false);
            $this->get('/groups/create')->assertForbidden();
            $this->post('/groups/create',['name'=>'No autorizado'])->assertForbidden();
        }
        $this->assertDatabaseCount('groups',0);
        $year = now('America/La_Paz')->year;
        foreach(['ADMIN','JEFE_UNIDAD'] as $index=>$role) {
            $this->actor($role);
            $this->get('/groups')->assertOk()->assertSee(route('groups.create'),false);
            $this->get('/groups/create')->assertOk()->assertSee('Nuevo sindicato o cooperativa');
            $this->post('/groups/create',['name'=>'Grupo '.$index,'description'=>'Descripción de prueba','year'=>(string) $year,
                'image'=>UploadedFile::fake()->image('logo.png')])->assertSessionHasNoErrors()->assertRedirect(route('groups.index'));
            $group=Group::orderByDesc('id')->firstOrFail();
            $this->assertSame('Grupo '.$index,$group->name);
            $this->assertSame($year.'-01-01',$group->creation_date);
            $this->assertSame('Descripción de prueba',$group->description);
            Storage::disk('public')->assertExists($group->logo_path);
        }
        $this->assertDatabaseCount('groups',2);
    }

    public function test_group_creation_validates_fields_before_writing_and_accepts_optional_fields(): void
    {
        $this->actor('ADMIN');
        $this->post('/groups/create',['name'=>['array'],'description'=>['array'],'year'=>now('America/La_Paz')->year+1,
            'image'=>UploadedFile::fake()->create('falso.png',2,'text/plain')])->assertSessionHasErrors(['name','description','year','image']);
        $this->assertDatabaseCount('groups',0);
        $this->post('/groups/create',['name'=>'Grupo sin datos opcionales'])->assertSessionHasNoErrors();
        $group=Group::firstOrFail();
        $this->assertNull($group->creation_date);
        $this->assertNull($group->description);
        $this->assertNull($group->logo_path);
    }

    public function test_group_edit_keeps_existing_logo_and_can_clear_the_foundation_year(): void
    {
        $group=new Group();$group->name='Anterior';$group->description='Texto';$group->creation_date='2020-01-01';
        $group->logo_path='img/group_logos/anterior.png';$group->save();
        $this->actor('JEFE_UNIDAD');
        $this->post('/groups/'.$group->id.'/edit',['name'=>'Actualizado','year'=>'','description'=>'Nuevo'])->assertSessionHasNoErrors();
        $group->refresh();
        $this->assertSame('Actualizado',$group->name);
        $this->assertNull($group->creation_date);
        $this->assertSame('img/group_logos/anterior.png',$group->logo_path);
    }
}
