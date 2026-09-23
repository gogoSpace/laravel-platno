<?php

declare(strict_types=1);

namespace Platno\Tests\Feature;

use Platno\Models\Page;
use Platno\Models\Publication;
use Platno\Tests\PersistenceTestCase;

final class PageManagementTest extends PersistenceTestCase
{
    // Prevent private previews leaking and history restoration changing immutable/public content.
    public function test_preview_history_restore_and_unpublish_preserve_publication_boundaries(): void
    {
        $this->install();
        $this->get('/platno/pages/1/preview')->assertForbidden();
        $this->authorizeEditor();
        $this->post('/platno/pages', ['title' => 'First', 'slug' => 'managed', 'text' => 'Published once']);
        $this->post('/platno/pages/1/publish', ['revision' => 1]);
        $snapshot = Publication::query()->firstOrFail()->toArray();
        $this->put('/platno/pages/1', ['revision' => 2, 'title' => 'Draft', 'text' => 'Private draft']);
        $this->get('/platno/pages/1/preview')->assertOk()->assertSee('Private draft')->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/pages/managed')->assertSee('Published once')->assertDontSee('Private draft');
        $this->get('/platno/pages/1/history')->assertOk();
        $this->post('/platno/pages/1/history/1/restore', ['revision' => 2])->assertConflict();
        $this->post('/platno/pages/1/history/1/restore', ['revision' => 3])->assertRedirect()->assertSessionHasNoErrors();
        self::assertSame('Published once', Page::query()->firstOrFail()->document['blocks'][0]['data']['text']);
        self::assertSame($snapshot, Publication::query()->firstOrFail()->toArray());
        $this->post('/platno/pages', ['title' => 'Other', 'slug' => 'other', 'text' => 'Other']);
        $this->post('/platno/pages/2/history/1/restore', ['revision' => 1])->assertNotFound();
        $this->post('/platno/pages/1/unpublish', ['revision' => 3])->assertConflict();
        $this->post('/platno/pages/1/unpublish', ['revision' => 4])->assertRedirect();
        $this->get('/pages/managed')->assertNotFound();
        $this->get('/platno/pages/1/history/1')->assertOk()->assertSee('Published once');
        self::assertSame($snapshot, Publication::query()->firstOrFail()->toArray());
    }

    // Prevent archived pages remaining public or accepting stale edits, while keeping recovery possible.
    public function test_archiving_is_recoverable_and_search_filters_do_not_mix_archived_content(): void
    {
        $this->install();
        $this->authorizeEditor();
        $this->post('/platno/pages', ['title' => 'Alpha', 'slug' => 'alpha', 'text' => 'Preserve']);
        $this->post('/platno/pages/1/publish', ['revision' => 1]);
        $this->post('/platno/pages/1/archive', ['revision' => 2])->assertRedirect();
        $this->get('/pages/alpha')->assertNotFound();
        $this->get('/platno')->assertViewHas('pages', fn ($pages) => $pages->isEmpty());
        $this->get('/platno?status=archived&search=Alpha')->assertViewHas('pages', fn ($pages) => $pages->count() === 1);
        $this->put('/platno/pages/1', ['revision' => 3, 'title' => 'Lost', 'text' => 'Lost'])->assertConflict();
        $this->post('/platno/pages/1/publish', ['revision' => 3])->assertConflict();
        $this->post('/platno/pages/1/unarchive', ['revision' => 3])->assertRedirect();
        $this->get('/platno/pages/1/edit')->assertOk();
        $this->get('/pages/alpha')->assertNotFound();
        self::assertSame('Preserve', Page::query()->firstOrFail()->document['blocks'][0]['data']['text']);
    }

    // Prevent theme configuration or package upgrades bypassing a host's Blade override.
    public function test_theme_tokens_and_host_view_overrides_are_used_for_public_and_private_rendering(): void
    {
        $this->install();
        $this->authorizeEditor();
        config(['platno.theme' => ['accent' => '#123456', 'width' => 900, 'font' => 'serif']]);
        $this->post('/platno/pages', ['title' => 'Theme', 'slug' => 'theme', 'text' => 'Content']);
        $this->post('/platno/pages/1/publish', ['revision' => 1]);
        $response = $this->get('/pages/theme')->assertOk();
        self::assertSame('#123456', $response->viewData('theme')['accent']);
        $override = $this->sandbox.'/views';
        mkdir($override.'/plugins', 0700, true);
        file_put_contents($override.'/plugins/text.blade.php', '<p>Host view: {{ $data["text"] }}</p>');
        $this->app['view']->prependNamespace('platno', $override);
        $this->app['view']->getFinder()->flush();
        $this->get('/pages/theme')->assertSee('Host view: Content');
        $this->get('/platno/pages/1/preview')->assertSee('Host view: Content');
    }
}
