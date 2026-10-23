<?php
namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;

trait IsolatedControlDatabase
{
    private ?string $testSchema = null;

    protected function configureControlDatabase(): void
    {
        if (getenv('TRAFICO_POSTGRES_TESTS') !== '1') {
            config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
            DB::purge('sqlite');
            return;
        }
        // This opt-in target is a separate local rehearsal cluster, never the installed database.
        config(['database.default' => 'pgsql', 'database.connections.pgsql' => [
            'driver' => 'pgsql', 'host' => '127.0.0.1', 'port' => 55439, 'database' => 'trafico_rehearsal',
            'username' => 'trafico_test', 'password' => '', 'charset' => 'utf8', 'prefix' => '', 'search_path' => 'public', 'sslmode' => 'disable',
        ]]);
        DB::purge('pgsql');
        DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp" WITH SCHEMA public');
        $this->testSchema = 'test_'.bin2hex(random_bytes(8));
        DB::statement('CREATE SCHEMA "'.$this->testSchema.'"');
        config(['database.connections.pgsql.search_path' => [$this->testSchema, 'public']]);
        DB::purge('pgsql');
    }

    protected function releaseControlDatabase(): void
    {
        if ($this->testSchema) {
            DB::connection('pgsql')->statement('DROP SCHEMA "'.$this->testSchema.'" CASCADE');
            DB::purge('pgsql');
            $this->testSchema = null;
        }
    }
}
