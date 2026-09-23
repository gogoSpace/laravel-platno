<?php

declare(strict_types=1);

namespace Platno\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Platno\Documents\DocumentValidator;
use Platno\Models\Page;
use Platno\Publishing\DraftManager;
use Platno\Publishing\PagePublisher;
use Platno\Tests\PersistenceTestCase;
use RuntimeException;

final class InstallationTest extends PersistenceTestCase
{
    // Prevent destructive reinstallation and accidental execution of host migrations.
    public function test_installation_is_scoped_and_preserves_host_configuration_and_content(): void
    {
        file_put_contents(config_path('platno.php'), '<?php return ["host-marker" => true];');
        file_put_contents(database_path('migrations/2099_01_01_000001_host_pending.php'), '<?php throw new RuntimeException("Host migration must not run");');
        Schema::create('host_data', fn (Blueprint $table) => $table->string('value'));
        $this->app['db']->table('host_data')->insert(['value' => 'retained']);
        $this->install();
        $page = $this->app->make(DraftManager::class)->create('first', 'First', $this->app->make(DocumentValidator::class)->text('Original'));
        $this->app->make(PagePublisher::class)->publish($page->getKey(), 1);
        $original = Page::query()->with('publication')->first()->toArray();

        $this->install();

        self::assertSame('<?php return ["host-marker" => true];', file_get_contents(config_path('platno.php')));
        self::assertSame('retained', $this->app['db']->table('host_data')->value('value'));
        self::assertSame($original, Page::query()->with('publication')->first()->toArray());
        self::assertSame(3, $this->app['db']->table('migrations')->count());
    }

    // Prevent adoption or replacement of unrelated existing tables.
    public function test_installation_refuses_an_unowned_table_before_creating_package_tables(): void
    {
        Schema::create('platno_publications', fn (Blueprint $table) => $table->string('host_value'));
        $this->app['db']->table('platno_publications')->insert(['host_value' => 'preserve']);

        try {
            Artisan::call('platno:install', ['--no-interaction' => true]);
            self::fail('Conflicting tables must be rejected.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('already exists', $exception->getMessage());
        }

        self::assertFalse(Schema::hasTable('platno_pages'));
        self::assertSame('preserve', $this->app['db']->table('platno_publications')->value('host_value'));
    }
}
