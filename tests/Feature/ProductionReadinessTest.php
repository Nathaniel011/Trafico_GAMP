<?php

namespace Tests\Feature;

use App\User;
use App\Models\Complaint;
use App\Models\Group;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    public function test_administrative_pages_require_a_session_before_querying_data(): void
    {
        foreach (['/reports/shops', '/groups', '/notifications', '/notices', '/inspectors'] as $uri) {
            $this->get($uri)->assertRedirect(route('login'));
        }
    }

    public function test_administrative_writes_require_a_session_before_validation_or_storage(): void
    {
        foreach (['/groups/1/edit', '/notifications/create', '/notices/create', '/inspectors/create', '/upload'] as $uri) {
            $this->post($uri, [])->assertRedirect(route('login'));
        }
    }

    public function test_public_registration_is_not_exposed(): void
    {
        $this->assertFalse(Route::has('register'));
    }

    public function test_certificate_editing_is_enforced_in_the_server(): void
    {
        $user = new User();
        $user->id = 1;
        $user->name = 'Consulta';
        $user->roles = '';
        $this->actingAs($user);

        $this->get('/certificate/edit/1')->assertForbidden();
        $this->post('/certificate/edit/1', [])->assertForbidden();
        $this->assertFalse(Gate::forUser($user)->allows('edit-certificates'));

        $user->roles = 'ADMINISTRATIVE';
        $this->assertFalse(Gate::forUser($user)->allows('edit-certificates'));
        $user->roles = 'CONSULTA,ADMIN';
        $this->assertTrue(Gate::forUser($user)->allows('edit-certificates'));
    }

    public function test_public_map_and_login_continue_to_render(): void
    {
        $this->get('/')->assertOk();
        $this->get('/login')->assertOk()->assertDontSee('href="'.url('/register').'"', false);
    }

    public function test_untrusted_notes_are_escaped_when_rendering_existing_views(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('state');
        });
        $user = new User();
        $user->id = 1;
        $user->name = 'Usuario';
        $this->actingAs($user);
        $payload = '<script>alert(1)</script>';
        $complaint = (new Complaint())->setRawAttributes(['id' => 1, 'notes' => $payload, 'state' => 'PENDIENTE']);
        $group = (new Group())->setRawAttributes(['id' => 1, 'name' => 'Grupo', 'description' => $payload]);
        $group->setRelation('transportLines', collect());

        foreach (['complaints.show' => $complaint, 'groups.show' => $group] as $view => $model) {
            $html = view($view, ['model' => $model, 'errors' => new \Illuminate\Support\ViewErrorBag()])->render();
            $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
            $this->assertStringNotContainsString($payload, $html);
        }
    }
}
