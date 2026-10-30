<?php

namespace Tests\Feature;

use App\Notice;
use App\UploadedFile as NoticeUpload;
use App\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LegacyNoticeCompatibilityTest extends TestCase
{
    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->useEnvironmentPath(sys_get_temp_dir());
        $app->loadEnvironmentFrom('certtrafico-notice-old-schema-no-env');
        $app->make(Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $paths = collect(glob(database_path('migrations/*.php')))
            ->filter(fn ($path) => basename($path) < '2026_')
            ->map(fn ($path) => 'database/migrations/'.basename($path))->values()->all();
        $this->artisan('migrate', ['--path' => $paths, '--force' => true])->assertExitCode(0);
        Storage::fake('local');
        $this->actor('ADMIN');
    }

    private function actor(string $role): void
    {
        $user = new User();
        $user->id = 1;
        $user->name = 'Operador de prueba';
        $user->roles = $role;
        $this->actingAs($user);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'number' => 'AV-123', 'date' => '15/06/2025 10:30', 'address' => 'Calle de prueba',
            'lat' => '-19.578', 'lng' => '-65.754', 'location_source' => 'MANUAL',
            'location_reason' => 'Punto confirmado en el lugar', 'location_confirmed' => '1',
            'file' => UploadedFile::fake()->image('notificacion.jpg'),
        ], $overrides);
    }

    private function historical(string $type, string $number, string $date): Notice
    {
        $notice = new Notice();
        $notice->type = $type;
        $notice->number = $number;
        $notice->notice_date = $date;
        $notice->address = 'Dirección histórica';
        $notice->lat = -19.578;
        $notice->lng = -65.754;
        $notice->save();
        return $notice;
    }

    public function test_original_notices_can_be_filtered_shown_and_updated_without_casetas(): void
    {
        $old = $this->historical('SHOPS', 'HIST-2025', '2025-12-31 23:30:00');
        $this->historical('SHOP', 'OTRO-2024', '2024-12-31 23:30:00');
        $this->historical('WAYS', 'VIA-2025', '2025-06-01 12:00:00');
        $this->get('/notices?mode=SHOP&year=2025')->assertOk()->assertSee('HIST-2025')->assertDontSee('OTRO-2024')->assertDontSee('VIA-2025');
        $this->get('/notices/'.$old->id)->assertOk()->assertSee('31/12/2025 23:30');
        $this->post('/notices/'.$old->id.'/edit', ['owner' => 'Propietario actualizado', 'document' => '12345', 'retirement' => '1'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('Propietario actualizado', $old->fresh()->owner);
        $this->assertTrue($old->fresh()->retirement);
        $this->assertFalse(Schema::hasTable('casetas'));
        $this->assertFalse(Schema::hasColumn('notices', 'year'));
    }

    public function test_both_modes_offer_direct_photograph_and_location_without_preupload(): void
    {
        foreach (['SHOP', 'WAYS'] as $mode) {
            $this->get('/notices/create?mode='.$mode)->assertOk()
                ->assertSee('name="file"', false)->assertSee('data-gps', false)
                ->assertDontSee('data-photo-upload', false)->assertDontSee('name="photo_id"', false)
                ->assertDontSee('name="caseta_id"', false);
            $this->post('/notices/create?mode='.$mode, $this->payload())
                ->assertSessionHasNoErrors()->assertRedirect(route('notices.index', ['mode' => $mode, 'year' => 2025]));
            $notice = Notice::where('type', $mode)->firstOrFail();
            $this->assertSame('2025-06-15 10:30:00', $notice->getRawOriginal('notice_date'));
            $photo = NoticeUpload::where('entity_id', $notice->id)->where('entity_type', 'NOTICE')->firstOrFail();
            Storage::disk('local')->assertExists($photo->path);
            $this->assertSame(route('notice-photos.show', $photo), $photo->url);
            $this->get($photo->url)->assertOk();
        }
        $this->assertDatabaseCount('notices', 2);
        $this->assertDatabaseCount('uploaded_files', 2);
    }

    public function test_duplicate_numbers_are_blocked_by_type_and_local_year(): void
    {
        $this->historical('SHOPS', 'AV-123', '2025-01-01 00:00:00');
        $this->post('/notices/create?mode=SHOP', $this->payload())->assertSessionHasErrors('number');
        $this->assertSame([], Storage::disk('local')->allFiles('caseta-photos'));
        $this->post('/notices/create?mode=SHOP', $this->payload(['date' => '15/06/2024 10:30']))->assertSessionHasNoErrors();
        $this->post('/notices/create?mode=WAYS', $this->payload())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('notices', 3);
    }

    public function test_legacy_requests_validate_photo_coordinates_dates_and_forbid_new_schema_fields(): void
    {
        foreach ([
            ['file' => null],
            ['file' => UploadedFile::fake()->create('falsa.jpg', 1, 'text/plain')],
            ['lat' => 91], ['lng' => -181], ['location_confirmed' => '0'],
            ['location_reason' => ''], ['location_source' => 'EXISTING'],
            ['location_source' => 'GPS', 'accuracy' => 5, 'located_at' => now()->subHour()->toIso8601String()],
            ['date' => now('America/La_Paz')->addDay()->format('d/m/Y H:i')],
            ['caseta_id' => 1], ['photo_id' => '6c5f5630-d5a2-4efa-800d-f04bb1c11111'],
        ] as $overrides) {
            $this->post('/notices/create?mode=SHOP', $this->payload($overrides))->assertSessionHasErrors();
        }
        $this->assertDatabaseCount('notices', 0);
        $this->assertDatabaseCount('uploaded_files', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('caseta-photos'));
    }

    public function test_legacy_creation_preserves_permission_checks(): void
    {
        $this->actor('ASESOR_LEGAL');
        $this->get('/notices/create?mode=SHOP')->assertForbidden();
        $this->post('/notices/create?mode=SHOP', $this->payload())->assertForbidden();
        $this->assertDatabaseCount('notices', 0);
    }

    public function test_failed_photo_record_rolls_back_notice_and_removes_private_file(): void
    {
        DB::statement("CREATE TRIGGER reject_notice_upload BEFORE INSERT ON uploaded_files BEGIN SELECT RAISE(ABORT, 'test upload failure'); END");
        $this->post('/notices/create?mode=SHOP', $this->payload())->assertStatus(500);
        $this->assertDatabaseCount('notices', 0);
        $this->assertDatabaseCount('uploaded_files', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('caseta-photos'));
    }
}
