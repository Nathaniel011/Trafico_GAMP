<?php

namespace Tests\Feature;

use App\Models\Inspector;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductionInspectorAuthenticationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // These authentication tests never connect to an installed system's database.
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        Schema::create('inspectors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('password');
            $table->boolean('enabled')->default(false);
            $table->timestamps();
        });
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_enabled_inspector_with_correct_password_receives_a_bearer_token_without_a_web_session(): void
    {
        $inspector = $this->createInspector();

        $response = $this->postJson('/api/auth/login', [
            'username' => $inspector->username,
            'password' => 'correct-password',
        ])->assertOk();

        $this->assertMatchesRegularExpression('/^\d+\|[A-Za-z0-9]+$/', $response->getContent());
        [$tokenId, $plainTextToken] = explode('|', $response->getContent(), 2);
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $tokenId,
            'tokenable_type' => Inspector::class,
            'tokenable_id' => $inspector->id,
            'name' => 'inspector_token',
            'token' => hash('sha256', $plainTextToken),
        ]);
        $this->assertGuest('web');
    }

    public function test_an_incorrect_password_is_rejected_and_does_not_issue_tokens(): void
    {
        $inspector = $this->createInspector();

        $this->postJson('/api/auth/login', [
            'username' => $inspector->username,
            'password' => 'incorrect-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('username');

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertGuest('web');
    }

    public function test_a_disabled_inspector_cannot_log_in_with_the_correct_password(): void
    {
        $inspector = $this->createInspector(false);

        $this->postJson('/api/auth/login', [
            'username' => $inspector->username,
            'password' => 'correct-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('username');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_an_unknown_username_is_rejected_and_does_not_issue_tokens(): void
    {
        $this->postJson('/api/auth/login', [
            'username' => 'unknown-inspector',
            'password' => 'correct-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('username');

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertDatabaseCount('inspectors', 0);
    }

    public function test_api_credentials_must_be_strings_instead_of_arrays(): void
    {
        $inspector = $this->createInspector();

        $this->postJson('/api/auth/login', [
            'username' => [$inspector->username],
            'password' => 'correct-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('username');

        $this->postJson('/api/auth/login', [
            'username' => $inspector->username,
            'password' => ['correct-password'],
        ])->assertUnprocessable()->assertJsonValidationErrors('password');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_guests_are_redirected_from_all_inspector_administration_routes_without_database_writes(): void
    {
        $inspector = $this->createInspector();
        $originalAttributes = $inspector->fresh()->getAttributes();

        foreach (['/inspectors', '/inspectors/create', '/inspectors/1/edit', '/inspectors/1'] as $uri) {
            $this->get($uri)->assertRedirect(route('login'));
        }
        $payload = [
            'name' => 'UNAUTHORIZED CHANGE',
            'username' => 'unauthorized',
            'password' => 'unauthorized-password',
            'password_confirmation' => 'unauthorized-password',
            'enabled' => '0',
        ];
        $this->post('/inspectors/create', $payload)->assertRedirect(route('login'));
        $this->post('/inspectors/1/edit', $payload)->assertRedirect(route('login'));

        $this->assertDatabaseCount('inspectors', 1);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertSame($originalAttributes, $inspector->fresh()->getAttributes());
    }

    public function test_existing_bearer_tokens_still_access_the_api_profile_without_a_web_session(): void
    {
        $inspector = $this->createInspector();
        $token = $inspector->createToken('existing-inspector-token');

        $this->withToken($token->plainTextToken)
            ->getJson('/api/auth/profile')
            ->assertOk()
            ->assertJsonPath('data.id', $inspector->id)
            ->assertJsonPath('data.username', $inspector->username)
            ->assertJsonMissingPath('data.password');

        $this->assertGuest('web');
    }

    public function test_bearer_token_logout_keeps_working_without_a_web_session(): void
    {
        $inspector = $this->createInspector();
        $token = $inspector->createToken('existing-inspector-token');

        $this->withToken($token->plainTextToken)
            ->getJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertGuest('web');
    }

    public function test_profile_and_logout_still_require_sanctum_authentication(): void
    {
        $this->getJson('/api/auth/profile')->assertUnauthorized();
        $this->getJson('/api/auth/logout')->assertUnauthorized();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    private function createInspector(bool $enabled = true): Inspector
    {
        $inspector = new Inspector();
        $inspector->name = 'Inspector de prueba';
        $inspector->username = 'inspector-prueba';
        $inspector->password = Hash::make('correct-password');
        $inspector->enabled = $enabled;
        $inspector->save();

        return $inspector;
    }
}
