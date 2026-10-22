<?php

namespace Tests\Feature;

use App\UploadedFile as UploadedFileRecord;
use App\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ProductionNoticeUploadTest extends TestCase
{
    private array $temporaryFiles = [];

    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        // Do not load the installed application's environment or credentials.
        $app->useEnvironmentPath(sys_get_temp_dir());
        $app->loadEnvironmentFrom('certtrafico-notice-upload-tests-no-env');
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        Storage::fake('public');

        require_once database_path('migrations/2025_05_26_141007_create_notices.php');
        require_once database_path('migrations/2025_06_02_014425_create_file.php');
        (new \CreateNotices())->up();
        (new \CreateFile())->up();
    }

    protected function tearDown(): void
    {
        try {
            parent::tearDown();
        } finally {
            foreach ($this->temporaryFiles as $file) {
                fclose($file);
            }
        }
    }

    public function test_notice_actions_require_a_web_session(): void
    {
        foreach (['/notices', '/notices/create', '/notices/1'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
        $this->post('/notices/create', $this->payload())->assertRedirect(route('login'));
        $this->post('/notices/1/edit', [])->assertRedirect(route('login'));
        $this->assertNothingStored();
    }

    public function test_executable_uploads_are_rejected(): void
    {
        $this->login();
        $file = $this->uploadedContent('shell.php', '<?php echo "test";', 'application/x-php');

        $this->post('/notices/create', $this->payload($file))->assertSessionHasErrors('file');
        $this->assertNothingStored();
    }

    public function test_client_mime_and_jpeg_name_cannot_disguise_executable_content(): void
    {
        $this->login();
        $file = $this->uploadedContent('foto.jpg', '<?php echo "test";', 'image/jpeg');

        $this->post('/notices/create', $this->payload($file))->assertSessionHasErrors('file');
        $this->assertNothingStored();
    }

    public function test_photos_larger_than_ten_megabytes_are_rejected(): void
    {
        $this->login();
        $file = UploadedFile::fake()->image('foto.png')->size(10241);

        $this->post('/notices/create', $this->payload($file))->assertSessionHasErrors('file');
        $this->assertNothingStored();
    }

    public function test_a_null_photo_is_rejected_without_creating_a_notice(): void
    {
        $this->login();

        $this->post('/notices/create', $this->payload(null))->assertSessionHasErrors('file');
        $this->assertNothingStored();
    }

    public function test_valid_photo_uses_a_uuid_and_the_content_extension(): void
    {
        $this->login();
        $generated = UploadedFile::fake()->image('content.png');
        $file = new UploadedFile($generated->getPathname(), 'nombre-cliente.jpeg', 'image/jpeg', null, true);

        $this->post('/notices/create', $this->payload($file))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('notices.index', ['mode' => 'SHOP']));

        $this->assertDatabaseCount('notices', 1);
        $this->assertDatabaseCount('uploaded_files', 1);
        $upload = UploadedFileRecord::firstOrFail();
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}\.png$/', $upload->name);
        $this->assertSame('png', $upload->extension);
        $this->assertSame('uploads/'.$upload->name, $upload->path);
        $this->assertSame(Storage::url($upload->path), $upload->url);
        $this->assertSame('NOTICE', $upload->entity_type);
        $this->assertSame(1, $upload->entity_id);
        Storage::disk('public')->assertExists($upload->path);
        $this->assertDatabaseHas('notices', ['id' => 1, 'number' => 'PRUEBA-PROD', 'type' => 'SHOP']);
    }

    public function test_metadata_failure_rolls_back_the_notice_and_removes_the_photo(): void
    {
        $this->login();
        $this->withoutExceptionHandling();
        UploadedFileRecord::creating(function () {
            throw new RuntimeException('Fallo de metadatos de prueba.');
        });

        try {
            $this->post('/notices/create', $this->payload(UploadedFile::fake()->image('foto.png')));
            $this->fail('Se esperaba el fallo de metadatos.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Fallo de metadatos de prueba.', $exception->getMessage());
        } finally {
            UploadedFileRecord::flushEventListeners();
        }

        $this->assertNothingStored();
    }

    private function login(): void
    {
        $user = new User();
        $user->id = 1;
        $user->name = 'Usuario de prueba';
        $this->actingAs($user);
    }

    private function payload(?UploadedFile $file = null): array
    {
        return [
            'mode' => 'SHOP',
            'number' => 'PRUEBA-PROD',
            'date' => '23/10/2026 10:00',
            'address' => 'Direccion de prueba',
            'lat' => '-19.5892639',
            'lng' => '-65.7536236',
            'file' => $file,
        ];
    }

    private function uploadedContent(string $name, string $content, string $claimedMime): UploadedFile
    {
        $temporary = tmpfile();
        fwrite($temporary, $content);
        $this->temporaryFiles[] = $temporary;

        return new UploadedFile(stream_get_meta_data($temporary)['uri'], $name, $claimedMime, null, true);
    }

    private function assertNothingStored(): void
    {
        $this->assertDatabaseCount('notices', 0);
        $this->assertDatabaseCount('uploaded_files', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }
}
