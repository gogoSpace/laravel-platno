<?php

declare(strict_types=1);

namespace Platno\Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Platno\Platno;
use Platno\Tests\Fixtures\HostEditorAccess;

abstract class PersistenceTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $connection = $this->app['db']->connection();
        // Abort before any write unless both configuration and the actual PDO are disposable.
        self::assertSame('sqlite', $connection->getDriverName());
        self::assertSame(':memory:', $connection->getDatabaseName());
        self::assertSame('', $connection->selectOne('PRAGMA database_list')->file);
        self::assertStringStartsWith(dirname(__DIR__).'/.sandbox/test-', config_path());
    }

    protected function defineRoutes($router): void
    {
        Route::middleware(HostEditorAccess::class)->group(fn () => Platno::editorRoutes());
        Platno::publicRoutes();
    }

    protected function install(): void
    {
        self::assertSame(0, Artisan::call('platno:install', ['--no-interaction' => true]));
    }

    protected function authorizeEditor(): void
    {
        $this->withHeaders(['X-Test-Host-Editor' => 'allowed']);
    }

    protected function denyEditor(): void
    {
        $this->withHeaders(['X-Test-Host-Editor' => 'denied']);
    }
}
