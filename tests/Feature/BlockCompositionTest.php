<?php

declare(strict_types=1);

namespace Platno\Tests\Feature;

use Illuminate\Support\Str;
use Platno\Models\Page;
use Platno\Models\Publication;
use Platno\Plugins\Text;
use Platno\Tests\Fixtures\NoticePlugin;
use Platno\Tests\PersistenceTestCase;

final class BlockCompositionTest extends PersistenceTestCase
{
    protected function defineEnvironment($application): void
    {
        parent::defineEnvironment($application);
        $application['config']->set('platno.plugins', [Text::class, NoticePlugin::class]);
        $application['view']->addNamespace('fixture', __DIR__.'/../Fixtures/views');
    }

    // Prevent multi-block edits and host inspector fields losing data or changing old publications.
    public function test_multiple_blocks_and_host_fields_round_trip_reorder_duplicate_remove_and_publish(): void
    {
        $this->install();
        $this->authorizeEditor();
        $first = ['id' => (string) Str::uuid(), 'type' => 'text', 'version' => 1, 'data' => ['text' => 'First block']];
        $notice = ['id' => (string) Str::uuid(), 'type' => 'example.notice', 'version' => 1, 'data' => ['message' => '<strong>A notice</strong>', 'tone' => 'important']];
        $document = ['version' => 1, 'blocks' => [$first, $notice]];
        $this->post('/platno/pages', ['title' => 'Composed', 'slug' => 'composed', 'document' => json_encode($document)])->assertRedirect();
        $page = Page::query()->firstOrFail();
        self::assertSame($document, $page->document);
        $response = $this->get('/platno/pages/'.$page->getKey().'/edit')->assertOk();
        self::assertSame($document, $response->viewData('document'));
        self::assertSame(['message', 'tone'], array_keys($response->viewData('catalog')[1]['fields']));
        $this->post('/platno/pages/'.$page->getKey().'/publish', ['revision' => 1])->assertRedirect();
        $snapshot = Publication::query()->firstOrFail()->toArray();
        $duplicate = [...$notice, 'id' => (string) Str::uuid(), 'data' => ['message' => 'Private duplicate', 'tone' => 'note']];
        $changed = ['version' => 1, 'blocks' => [$duplicate, $notice]];
        $this->put('/platno/pages/'.$page->getKey(), ['revision' => 2, 'title' => 'Composed', 'document' => $changed])->assertRedirect();
        self::assertSame($changed, $page->fresh()->document);
        self::assertSame($snapshot, Publication::query()->firstOrFail()->toArray());
        $this->get('/pages/composed')->assertSee('First block')->assertSee('&lt;strong&gt;', false)->assertDontSee('Private duplicate');
        $this->post('/platno/pages/'.$page->getKey().'/publish', ['revision' => 3])->assertRedirect();
        $this->get('/pages/composed')->assertSee('Private duplicate')->assertDontSee('First block');
    }

    // Prevent old single-text clients silently discarding a richer document or duplicated identities.
    public function test_lossy_legacy_saves_and_duplicate_block_identities_are_rejected(): void
    {
        $this->install();
        $this->authorizeEditor();
        $block = ['id' => (string) Str::uuid(), 'type' => 'text', 'version' => 1, 'data' => ['text' => 'Keep']];
        $document = ['version' => 1, 'blocks' => [$block, [...$block, 'id' => (string) Str::uuid()]]];
        $this->post('/platno/pages', ['title' => 'Keep', 'slug' => 'keep', 'document' => $document])->assertRedirect();
        $this->putJson('/platno/pages/1', ['title' => 'Replace', 'revision' => 1, 'text' => 'Lost'])->assertUnprocessable();
        $this->putJson('/platno/pages/1', ['title' => 'Replace', 'revision' => 1, 'document' => ['version' => 1, 'blocks' => [$block, $block]]])->assertUnprocessable();
        self::assertSame($document, Page::query()->firstOrFail()->document);
        self::assertSame(1, Page::query()->firstOrFail()->revision);
    }
}
