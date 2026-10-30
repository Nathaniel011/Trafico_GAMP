<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Inspector;
use App\User;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Elibyy\TCPDF\Facades\TCPDF as PDF;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class Php82CompatibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Never use an installed application's database or PostgreSQL-only migrations.
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
    }

    public function test_public_home_and_existing_login_form_render(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertViewIs('public.parkings')
            ->assertSee('PUNTOS DE PARQUEO');

        $this->get('/login')
            ->assertOk()
            ->assertViewIs('auth.login')
            ->assertSee('name="username"', false)
            ->assertSee('name="_token"', false);
    }

    public function test_certificate_route_still_redirects_guests_to_login(): void
    {
        $this->get('/certificate')->assertRedirect(route('login'));
    }

    public function test_certificate_datatable_retains_dates_and_action_links(): void
    {
        $this->createCertificatesTable();
        DB::table('certificates')->insert([
            'solicitante' => 'INSPECCION DE PRUEBA',
            'created_at' => '2024-03-15 10:30:00',
            'updated_at' => '2024-03-15 10:30:00',
        ]);

        $this->actingAs($this->webUser('ADMIN'));

        $response = $this->getJson('/certificate/data?draw=1&start=0&length=10');
        $response->assertOk()
            ->assertJsonPath('draw', 1)
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.solicitante', 'INSPECCION DE PRUEBA')
            ->assertJsonPath('data.0.created_at', '2024-03-15 10:30:00');

        $actions = $response->json('data.0.actions');
        $this->assertStringContainsString(route('certificate.legal.edit', 1), $actions);
        $this->assertStringContainsString(route('certificate.show', 1), $actions);
        $this->assertStringContainsString(route('certificate.showqr', 1), $actions);
    }

    public function test_datatable_handles_an_existing_user_with_nullable_roles(): void
    {
        $this->createCertificatesTable();
        DB::table('certificates')->insert([
            'solicitante' => 'PRUEBA SIN ROL',
            'created_at' => '2024-03-15 10:30:00',
            'updated_at' => '2024-03-15 10:30:00',
        ]);
        $this->actingAs($this->webUser(null));

        $response = $this->getJson('/certificate/data?draw=1&start=0&length=10');
        $response->assertOk()->assertJsonPath('recordsTotal', 1);
        $this->assertStringNotContainsString('Editar', $response->json('data.0.actions'));
    }

    public function test_certificate_can_still_be_saved_without_optional_mobile_number(): void
    {
        $this->createCertificatesTable();
        $this->actingAs($this->webUser('ADMIN'));

        $this->post('/certificate/create', [
            'type' => 'CASETA',
            'mode' => 'NUEVO',
            'solicitante' => 'Persona de prueba',
            'ci' => 'PRUEBA-82',
            'celular' => null,
            'rubro' => 'Venta de dulces',
            'direccion' => 'Direccion de prueba',
            'dim_ancho' => '1.5',
            'dim_largo' => '2',
            'tipo_via' => 'ACERA',
            'dim_via' => '4',
            'dim_perc_ocupado' => null,
            'ref_product' => 'VENTA DE DULCES',
            'ref_turn' => 'MAÑANA',
            'ref_start' => '08:00',
            'ref_end' => '12:00',
            'color' => 'Blanco',
            'material' => 'Metal',
            'inspection_date' => '15/03/2024',
            'technical_proceed' => '1',
            'legal_proceed' => '1',
            'lat' => '-19.5892639',
            'lng' => '-65.7536236',
            'conclusions' => 'Observacion de prueba',
            'location_source' => 'MANUAL', 'location_reason' => 'Ubicación confirmada en campo', 'location_confirmed' => 1,
        ])->assertSessionHasNoErrors()
            ->assertRedirect(route('certificate.index'));

        $certificate = Certificate::findOrFail(1);
        $this->assertSame('PERSONA DE PRUEBA', $certificate->solicitante);
        $this->assertSame('', $certificate->celular);
        $this->assertNull($certificate->inspection_date, 'The optional phone must not prevent legal registration or complete the technical stage.');
    }

    public function test_sanctum_can_read_a_preexisting_token_without_expiry_column(): void
    {
        $inspector = $this->createInspectorAndTokenTable(false);
        $plainTextToken = Str::random(40);
        $tokenId = DB::table('personal_access_tokens')->insertGetId([
            'tokenable_type' => Inspector::class,
            'tokenable_id' => $inspector->id,
            'name' => 'inspector_token',
            'token' => hash('sha256', $plainTextToken),
            'abilities' => json_encode(['*']),
            'created_at' => '2024-03-15 10:30:00',
            'updated_at' => '2024-03-15 10:30:00',
        ]);

        $this->withToken($tokenId.'|'.$plainTextToken)
            ->getJson('/api/auth/profile')
            ->assertOk()
            ->assertJsonPath('data.id', $inspector->id)
            ->assertJsonPath('data.username', 'inspector-prueba')
            ->assertJsonPath('data.created_at', '15/03/2024 10:30');

        $this->assertNotNull(DB::table('personal_access_tokens')->value('last_used_at'));
    }

    public function test_new_sanctum_tokens_use_a_nullable_expiry_on_the_updated_schema(): void
    {
        $inspector = $this->createInspectorAndTokenTable(true);
        $newToken = $inspector->createToken('inspector_token');

        $this->assertNull($newToken->accessToken->expires_at);
        $this->withToken($newToken->plainTextToken)
            ->getJson('/api/auth/profile')
            ->assertOk()
            ->assertJsonPath('data.username', 'inspector-prueba');
    }

    public function test_expiry_migration_preserves_existing_tokens_and_can_be_reversed(): void
    {
        $inspector = $this->createInspectorAndTokenTable(false);
        $hash = hash('sha256', 'token-anterior-a-la-migracion');
        $tokenId = DB::table('personal_access_tokens')->insertGetId([
            'tokenable_type' => Inspector::class,
            'tokenable_id' => $inspector->id,
            'name' => 'inspector_token',
            'token' => $hash,
            'abilities' => json_encode(['*']),
            'created_at' => '2024-03-15 10:30:00',
            'updated_at' => '2024-03-15 10:30:00',
        ]);

        $migration = require database_path('migrations/2026_10_22_000000_add_expires_at_to_personal_access_tokens.php');
        $migration->up();
        // Existing installations may already have the nullable expiry column.
        $migration->up();

        $this->assertTrue(Schema::hasColumn('personal_access_tokens', 'expires_at'));
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $tokenId,
            'token' => $hash,
            'expires_at' => null,
        ]);
        $newToken = $inspector->createToken('nuevo-token');
        $this->assertNull($newToken->accessToken->expires_at);

        $migration->down();

        $this->assertFalse(Schema::hasColumn('personal_access_tokens', 'expires_at'));
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $tokenId, 'token' => $hash]);
        $this->assertDatabaseCount('personal_access_tokens', 2);
    }

    public function test_modern_user_factory_retains_the_existing_username_schema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        $user = User::factory()->create();

        $this->assertInstanceOf(User::class, $user);
        $this->assertNotEmpty($user->username);
        $this->assertNotEmpty($user->password);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'username' => $user->username,
        ]);
    }

    public function test_legacy_notices_do_not_infer_a_third_notice_deadline(): void
    {
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('number');
            $table->dateTime('notice_date');
            $table->string('owner')->nullable();
            $table->string('address');
            $table->boolean('retirement')->nullable();
            $table->timestamps();
        });
        // The shared admin layout displays the pending complaint count.
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('state');
        });
        $this->actingAs($this->webUser('ADMIN'));
        Carbon::setTestNow(Carbon::parse('2026-10-22 12:00:00'));

        try {
            $cases = [
                'ANTES-48H' => [Carbon::now()->subHours(48)->addSecond(), 'NOTIFICADO'],
                'EXACTO-48H' => [Carbon::now()->subHours(48), 'PENDIENTE'],
                'ANTES-72H' => [Carbon::now()->subHours(72)->addSecond(), 'PENDIENTE'],
                'EXACTO-72H' => [Carbon::now()->subHours(72), 'VENCIDO'],
                'FECHA-FUTURA' => [Carbon::now()->addHours(48), 'PENDIENTE'],
            ];
            foreach ($cases as $number => [$date]) {
                DB::table('notices')->insert([
                    'type' => 'SHOP',
                    'number' => $number,
                    'notice_date' => $date->format('Y-m-d H:i:s'),
                    'address' => 'Direccion de prueba',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }

            $response = $this->get('/notices?mode=SHOP')->assertOk();
            $document = new DOMDocument();
            $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
            $rows = (new DOMXPath($document))->query('//*[@id="main-table"]/tbody/tr');
            $this->assertCount(count($cases), $rows);

            foreach ($rows as $row) {
                $columns = $row->getElementsByTagName('td');
                $number = explode(' / ', trim($columns->item(0)->textContent))[0];
                $this->assertSame('Histórico sin secuencia verificada', trim($columns->item(4)->textContent), $number);
            }
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_certificate_qr_endpoint_generates_a_real_pdf_without_external_services(): void
    {
        $this->createCertificatesTable();
        DB::table('certificates')->insert([
            'solicitante' => 'PERSONA DE PRUEBA',
            'code' => '00000000-0000-4000-8000-000000000082',
            'created_at' => '2024-03-15 10:30:00',
            'updated_at' => '2024-03-15 10:30:00',
        ]);
        $this->actingAs($this->webUser('ADMIN'));

        $response = $this->get('/certificate/qr/1');
        $pdf = $response->getContent();

        $response->assertOk();
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertStringContainsString('/Type /Page', $pdf);
        $this->assertStringEndsWith('%%EOF', rtrim($pdf));
    }

    public function test_complete_certificate_pdf_uses_the_real_engine_with_an_isolated_map_image(): void
    {
        $this->createCertificatesTable();
        DB::table('certificates')->insert([
            'tipo' => 'CASETA',
            'modalidad' => 'NUEVO',
            'solicitante' => 'PERSONA DE PRUEBA',
            'ci' => 'PRUEBA-82',
            'celular' => '',
            'rubro' => 'VENTA DE DULCES',
            'direccion' => 'DIRECCION DE PRUEBA',
            'dim_ancho' => 1.5,
            'dim_largo' => 2,
            'tipo_via' => 'ACERA',
            'dim_via' => 4,
            'dim_perc_ocupado' => 25,
            'ref_product' => 'VENTA DE DULCES',
            'ref_turn' => 'MAÑANA',
            'ref_start' => '08:00:00',
            'ref_end' => '12:00:00',
            'color' => 'BLANCO',
            'material' => 'METAL',
            'inspection_date' => '2024-03-15',
            'technical_proceed' => true,
            'legal_proceed' => true,
            'lat' => -19.5892639,
            'lng' => -65.7536236,
            'conclusions' => 'OBSERVACION DE PRUEBA',
            'code' => '00000000-0000-4000-8000-000000000082',
            'created_at' => '2024-03-15 10:30:00',
            'updated_at' => '2024-03-15 10:30:00',
        ]);
        $this->actingAs($this->webUser('ADMIN'));
        $capture = app(\App\Services\CertificateMapCapture::class);
        $certificate = Certificate::findOrFail(1);
        $this->post('/certificate/map-capture/1', [
            'version' => $capture->version($certificate),
            'image' => \Illuminate\Http\UploadedFile::fake()->image('croquis.png', 640, 400),
        ], ['Accept' => 'application/json'])->assertOk();
        $this->assertNotNull($capture->get($certificate));
        $owner = auth()->user();
        $other = clone $owner;
        $other->id = 987654;
        $this->actingAs($other);
        $this->assertNull($capture->get($certificate));
        $this->actingAs($owner);
        $this->post('/certificate/map-capture/1', [
            'version' => 'version-antigua',
            'image' => \Illuminate\Http\UploadedFile::fake()->image('croquis.png', 640, 400),
        ], ['Accept' => 'application/json'])->assertStatus(409);
        $this->post('/certificate/map-capture/1', [
            'version' => $capture->version($certificate),
            'image' => \Illuminate\Http\UploadedFile::fake()->image('croquis.png', 10, 10),
        ], ['Accept' => 'application/json'])->assertStatus(422);
        $this->withoutExceptionHandling();

        $response = $this->get('/certificate/pdf/1');
        $pdf = $response->getContent();

        $response->assertOk();
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertGreaterThanOrEqual(2, preg_match_all('~/Type /Page\b~', $pdf));
        $this->assertStringEndsWith('%%EOF', rtrim($pdf));
    }

    public function test_local_storage_and_file_sessions_survive_a_round_trip(): void
    {
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'certtrafico-php82-'.Str::uuid();
        mkdir($directory, 0700);
        config([
            'filesystems.disks.php82_test' => [
                'driver' => 'local',
                'root' => $directory,
                'throw' => true,
            ],
            'session.files' => $directory,
        ]);

        $disk = Storage::disk('php82_test');
        $sessionId = Str::random(40);
        try {
            $this->assertTrue($disk->put('compatibility.txt', 'PHP 8.2'));
            $this->assertSame('PHP 8.2', $disk->get('compatibility.txt'));

            $session = (new SessionManager($this->app))->driver('file');
            $session->setId($sessionId);
            $session->start();
            $session->put('php82_probe', 'sesion conservada');
            $session->save();

            $restored = (new SessionManager($this->app))->driver('file');
            $restored->setId($sessionId);
            $restored->start();
            $this->assertSame('sesion conservada', $restored->get('php82_probe'));
        } finally {
            $disk->delete('compatibility.txt');
            $disk->delete($sessionId);
            rmdir($directory);
        }
    }

    private function webUser(?string $roles): User
    {
        $user = new User();
        $user->id = 1;
        $user->name = 'Usuario de prueba';
        $user->roles = $roles;

        return $user;
    }

    private function createCertificatesTable(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            foreach ([
                'tipo', 'modalidad', 'solicitante', 'ci', 'celular', 'rubro',
                'direccion', 'tipo_via', 'ref_product', 'ref_turn', 'ref_start',
                'ref_end', 'color', 'material', 'conclusions', 'inspection_date', 'code', 'location_source', 'location_reason',
            ] as $column) {
                $table->string($column)->nullable();
            }
            foreach (['dim_ancho', 'dim_largo', 'dim_via', 'dim_perc_ocupado', 'lat', 'lng', 'accuracy'] as $column) {
                $table->double($column)->nullable();
            }
            $table->timestamp('located_at')->nullable();
            $table->boolean('technical_proceed')->nullable();
            $table->boolean('legal_proceed')->nullable();
            $table->timestamps();
        });
        Schema::create('certificate_revisions', function (Blueprint $table) {
            $table->id(); $table->foreignId('certificate_id'); $table->unsignedInteger('revision');
            $table->json('snapshot'); $table->string('actor_type',80)->nullable(); $table->unsignedBigInteger('actor_id')->nullable();
            $table->text('reason'); $table->timestamp('created_at'); $table->unique(['certificate_id','revision']);
        });

    }

    private function createInspectorAndTokenTable(bool $includeExpiry): Inspector
    {
        Schema::create('inspectors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username');
            $table->timestamps();
        });
        Schema::create('personal_access_tokens', function (Blueprint $table) use ($includeExpiry) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            if ($includeExpiry) {
                $table->timestamp('expires_at')->nullable();
            }
            $table->timestamps();
        });
        DB::table('inspectors')->insert([
            'id' => 1,
            'name' => 'Inspector de prueba',
            'username' => 'inspector-prueba',
            'created_at' => '2024-03-15 10:30:00',
            'updated_at' => '2024-03-15 10:30:00',
        ]);

        return Inspector::findOrFail(1);
    }
}
