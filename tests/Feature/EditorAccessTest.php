<?php

declare(strict_types=1);

namespace Platno\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Platno\Models\Page;
use Platno\Tests\PersistenceTestCase;

final class EditorAccessTest extends PersistenceTestCase
{
    // Prevent package routes bypassing a consuming application's explicit middleware policy.
    public function test_host_middleware_controls_editor_reads_and_all_mutations(): void
    {
        $this->install();
        foreach (['/platno', '/platno/media', '/platno/pages/1/preview', '/platno/pages/1/history'] as $address) {
            $this->get($address)->assertForbidden();
        }
        foreach (['/platno/pages', '/platno/media', '/platno/pages/1/publish', '/platno/pages/1/unpublish', '/platno/pages/1/archive', '/platno/pages/1/unarchive', '/platno/pages/1/history/1/restore'] as $address) {
            $this->post($address)->assertForbidden();
        }
        $this->put('/platno/pages/1')->assertForbidden();
        self::assertSame(0, Page::query()->count());
        $this->authorizeEditor();
        $this->get('/platno')->assertOk();
        $this->post('/platno/pages', ['title' => 'Host decision', 'slug' => 'host-decision', 'text' => 'Content'])->assertRedirect();
        self::assertSame(1, Page::query()->count());
        $this->denyEditor();
        $this->put('/platno/pages/1', ['revision' => 1, 'title' => 'Denied', 'text' => 'Lost'])->assertForbidden();
        self::assertSame('Host decision', Page::query()->firstOrFail()->title);
    }

    // Prevent accidental reintroduction of package identities or authentication during installation.
    public function test_package_has_no_authentication_routes_tables_or_guard(): void
    {
        $this->install();
        $this->authorizeEditor();
        $this->get('/platno/login')->assertNotFound();
        $this->post('/platno/login')->assertNotFound();
        $this->post('/platno/logout')->assertNotFound();
        self::assertArrayNotHasKey('platno', config('auth.guards'));
        self::assertFalse(Schema::hasTable('platno_administrators'));
        self::assertFalse(Schema::hasTable('platno_access_keys'));
    }
}
