<?php

declare(strict_types=1);

namespace Platno\Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Platno\PlatnoServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected string $sandbox;

    protected function setUp(): void
    {
        $this->sandbox = dirname(__DIR__).'/.sandbox/test-'.bin2hex(random_bytes(12));
        foreach (['config', 'database/migrations', 'storage/framework/views', 'storage/framework/sessions', 'storage/logs'] as $directory) {
            mkdir($this->sandbox.'/'.$directory, 0700, true);
        }

        parent::setUp();
    }

    protected function tearDown(): void
    {
        try {
            parent::tearDown();
        } finally {
            (new Filesystem)->deleteDirectory($this->sandbox);
        }
    }

    /** @param Application $application */
    protected function getPackageProviders($application): array
    {
        return [PlatnoServiceProvider::class];
    }

    /** @param Application $application */
    protected function defineEnvironment($application): void
    {
        $application->useStoragePath($this->sandbox.'/storage');
        $application->useConfigPath($this->sandbox.'/config');
        $application->useDatabasePath($this->sandbox.'/database');
        $application['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $application['config']->set('view.compiled', $this->sandbox.'/storage/framework/views');
        $application['config']->set('database.default', 'sqlite');
        $application['config']->set('database.connections', [
            'sqlite' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true],
        ]);
        $application['config']->set('filesystems.disks', ['platno' => ['driver' => 'local', 'root' => $this->sandbox.'/storage/media', 'visibility' => 'private', 'throw' => true]]);
        $application['config']->set('cache.default', 'array');
        $application['config']->set('session.driver', 'array');
        $application['config']->set('mail.default', 'array');
    }
}
