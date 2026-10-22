<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Resources\PathPointResource;
use App\Models\PathPoint;
use ErrorException;
use Illuminate\Cache\CacheManager;
use Illuminate\Http\Request;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class Laravel13CompatibilityTest extends TestCase
{
    private const POST_URI = '/__compatibility/laravel13/request-forgery';

    protected function setUp(): void
    {
        parent::setUp();

        // Exercise the real web stack while disabling only its test-environment bypass.
        $middleware = new class($this->app, $this->app['encrypter']) extends VerifyCsrfToken {
            protected function runningUnitTests()
            {
                return false;
            }
        };
        $this->app->instance(VerifyCsrfToken::class, $middleware);

        Route::middleware('web')->post(self::POST_URI, function (Request $request) {
            return response()->json([
                'accepted' => true,
                'session_marker' => $request->session()->get('session_marker'),
            ]);
        });
    }

    public function test_a_matching_form_token_is_accepted_by_the_real_csrf_middleware(): void
    {
        $token = Str::random(40);

        $this->withSession(['_token' => $token, 'session_marker' => 'retained'])
            ->postJson(self::POST_URI, ['_token' => $token])
            ->assertOk()
            ->assertJsonPath('accepted', true)
            ->assertJsonPath('session_marker', 'retained')
            ->assertCookie(config('session.cookie'))
            ->assertCookie('XSRF-TOKEN');
    }

    public function test_a_matching_csrf_header_is_still_accepted_for_existing_ajax_requests(): void
    {
        $token = Str::random(40);

        $this->withSession(['_token' => $token])
            ->withHeader('X-CSRF-TOKEN', $token)
            ->postJson(self::POST_URI)
            ->assertOk()
            ->assertJsonPath('accepted', true);
    }

    public function test_missing_csrf_token_is_rejected_when_origin_cannot_be_verified(): void
    {
        $this->withSession(['_token' => Str::random(40)])
            ->postJson(self::POST_URI)
            ->assertStatus(419);
    }

    public function test_an_incorrect_csrf_token_is_rejected(): void
    {
        $this->withSession(['_token' => Str::random(40)])
            ->postJson(self::POST_URI, ['_token' => 'incorrect-token'])
            ->assertStatus(419);
    }

    public function test_laravel13_accepts_a_verified_same_origin_request_without_a_form_token(): void
    {
        $this->withSession(['_token' => Str::random(40)])
            ->withHeader('Sec-Fetch-Site', 'same-origin')
            ->postJson(self::POST_URI)
            ->assertOk()
            ->assertJsonPath('accepted', true);
    }

    public function test_same_site_requests_still_require_a_token_by_default(): void
    {
        $this->withSession(['_token' => Str::random(40)])
            ->withHeader('Sec-Fetch-Site', 'same-site')
            ->postJson(self::POST_URI)
            ->assertStatus(419);
    }

    public function test_cross_site_requests_without_a_valid_token_are_rejected(): void
    {
        $this->withSession(['_token' => Str::random(40)])
            ->withHeader('Sec-Fetch-Site', 'cross-site')
            ->postJson(self::POST_URI)
            ->assertStatus(419);
    }

    public function test_cross_site_metadata_can_fall_back_to_a_matching_csrf_token(): void
    {
        $token = Str::random(40);

        $this->withSession(['_token' => $token])
            ->withHeader('Sec-Fetch-Site', 'cross-site')
            ->postJson(self::POST_URI, ['_token' => $token])
            ->assertOk()
            ->assertJsonPath('accepted', true);
    }

    public function test_session_cookie_name_and_cache_prefix_keep_existing_environment_conventions(): void
    {
        $applicationName = env('APP_NAME', 'laravel');
        $this->assertSame(
            env('SESSION_COOKIE', Str::slug($applicationName, '_').'_session'),
            config('session.cookie')
        );
        $this->assertSame(
            env('CACHE_PREFIX', Str::slug($applicationName, '_').'_cache'),
            config('cache.prefix')
        );

        $this->get('/login')->assertOk()->assertCookie(config('session.cookie'));
    }

    public function test_existing_php_serialized_sessions_can_still_be_read_and_saved_in_memory(): void
    {
        $session = (new SessionManager($this->app))->driver('array');
        $sessionId = Str::random(40);
        $legacyData = [
            '_token' => Str::random(40),
            'login_web_existing_guard' => 42,
            'session_marker' => 'session-before-laravel13',
        ];
        $session->getHandler()->write($sessionId, serialize($legacyData));
        $session->setId($sessionId);
        $session->start();

        $this->assertSame($legacyData['_token'], $session->token());
        $this->assertSame(42, $session->get('login_web_existing_guard'));
        $this->assertSame('session-before-laravel13', $session->get('session_marker'));
        $session->save();

        $rawSession = $session->getHandler()->read($sessionId);
        $this->assertStringStartsWith('a:', $rawSession);
        $this->assertSame(42, unserialize($rawSession, ['allowed_classes' => false])['login_web_existing_guard']);
    }

    public function test_scalar_cache_values_and_arrays_round_trip_with_serialization_enabled(): void
    {
        config(['cache.stores.array.serialize' => true]);
        $cache = (new CacheManager($this->app))->store('array');
        $value = ['count' => 3, 'state' => 'PENDIENTE', 'enabled' => true];

        $cache->put('laravel13-regression', $value, 60);

        $this->assertSame($value, $cache->get('laravel13-regression'));
        $this->assertTrue($cache->forget('laravel13-regression'));
        $this->assertNull($cache->get('laravel13-regression'));
    }

    public function test_mysql_configuration_loads_without_deprecated_php85_pdo_constants(): void
    {
        if (! extension_loaded('pdo_mysql')) {
            $this->markTestSkipped('The MySQL PDO extension is needed to exercise its configuration branch.');
        }

        // Reload configuration only; this test never opens a MySQL connection.
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if ($severity === E_DEPRECATED || $severity === E_USER_DEPRECATED) {
                throw new ErrorException($message, 0, $severity, $file, $line);
            }

            return false;
        });
        try {
            $database = require config_path('database.php');
        } finally {
            restore_error_handler();
        }

        $this->assertIsArray($database['connections']['mysql']['options']);
    }

    public function test_path_point_resource_keeps_numeric_coordinates_and_existing_api_keys(): void
    {
        $point = new PathPoint();
        // PostgreSQL PDO may return decimal coordinates as strings.
        $point->setRawAttributes(['id' => 17, 'lat' => '-19.5892639', 'lng' => '-65.7536236']);
        $request = Request::create('/api/routes/1/path');
        $resource = new PathPointResource($point);
        $expected = ['id' => 17, 'lat' => -19.5892639, 'lon' => -65.7536236];

        $this->assertSame($expected, $resource->resolve($request));
        $this->assertSame($expected, $resource->response($request)->getData(true)['data']);
    }
}
