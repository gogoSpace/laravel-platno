<?php

declare(strict_types=1);

namespace Platno\Tests\Feature;

use Illuminate\Validation\ValidationException;
use Platno\Documents\DocumentValidator;
use Platno\Models\Page;
use Platno\Models\Publication;
use Platno\Publishing\DraftManager;
use Platno\Publishing\PagePublisher;
use Platno\Tests\PersistenceTestCase;
use RuntimeException;

final class PublicationTest extends PersistenceTestCase
{
    // Prevent draft leakage, executable text and publication pointers tracking mutable drafts.
    public function test_real_editor_flow_keeps_drafts_private_and_publications_immutable(): void
    {
        $this->install();
        $this->authorizeEditor();
        $this->post('/platno/pages', ['title' => '<b>First</b>', 'slug' => 'first', 'text' => '<script>unsafe()</script>'])->assertRedirect();
        $page = Page::query()->firstOrFail();
        $this->get('/pages/first')->assertNotFound();
        $this->get('/platno/pages/'.$page->getKey().'/edit')->assertOk();
        $this->post('/platno/pages/'.$page->getKey().'/publish', ['revision' => 1])->assertRedirect();
        $firstPublication = Publication::query()->firstOrFail()->toArray();
        $this->put('/platno/pages/'.$page->getKey(), ['revision' => 2, 'title' => 'Next title', 'text' => 'Private next version'])->assertRedirect();
        $this->denyEditor();

        $this->get('/pages/first')->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>', false)
            ->assertSee('&lt;b&gt;First&lt;/b&gt;', false)->assertDontSee('Private next version');
        self::assertSame($firstPublication, Publication::query()->firstOrFail()->toArray());
        self::assertSame('Next title', $page->fresh()->title);
    }

    // Prevent two editors or repeated publish requests silently overwriting each other.
    public function test_stale_saves_and_publications_conflict_and_preserve_submitted_content(): void
    {
        $this->install();
        $this->authorizeEditor();
        $this->post('/platno/pages', ['title' => 'Original', 'slug' => 'conflict', 'text' => 'Original']);
        $page = Page::query()->firstOrFail();
        $this->put('/platno/pages/'.$page->getKey(), ['revision' => 1, 'title' => 'Winner', 'text' => 'New content'])->assertRedirect();
        $this->put('/platno/pages/'.$page->getKey(), ['revision' => 1, 'title' => 'Unsaved', 'text' => '<script>recover me</script>'])
            ->assertConflict()->assertSee('&lt;script&gt;recover me&lt;/script&gt;', false);
        $this->post('/platno/pages/'.$page->getKey().'/publish', ['revision' => 1])->assertConflict();
        self::assertSame(0, Publication::query()->count());
        self::assertSame('Winner', $page->fresh()->title);
        $this->post('/platno/pages/'.$page->getKey().'/publish', ['revision' => 2])->assertRedirect();
        $this->post('/platno/pages/'.$page->getKey().'/publish', ['revision' => 2])->assertConflict();
        self::assertSame(1, Publication::query()->count());
    }

    // Prevent partially published state when persistence fails after snapshot insertion.
    public function test_publication_failure_rolls_back_snapshot_revision_and_pointer(): void
    {
        $this->install();
        $page = $this->app->make(DraftManager::class)->create('atomic', 'Atomic', $this->app->make(DocumentValidator::class)->text('Content'));
        $originalPublication = $this->app->make(PagePublisher::class)->publish($page->getKey(), 1);
        Publication::created(fn () => throw new RuntimeException('Injected storage failure'));

        try {
            $this->app->make(PagePublisher::class)->publish($page->getKey(), 2);
            self::fail('The injected failure must abort publication.');
        } catch (RuntimeException $exception) {
            self::assertSame('Injected storage failure', $exception->getMessage());
        } finally {
            Publication::flushEventListeners();
            Publication::clearBootedModels();
        }

        self::assertSame(1, Publication::query()->count());
        self::assertSame(2, $page->fresh()->revision);
        self::assertSame($originalPublication->getKey(), $page->fresh()->publication_id);
    }

    // Prevent duplicate addresses and malformed plugin content entering storage.
    public function test_invalid_content_and_duplicate_slugs_do_not_change_existing_content(): void
    {
        $this->install();
        $this->authorizeEditor();
        $this->post('/platno/pages', ['title' => 'Original', 'slug' => 'same', 'text' => 'Keep']);
        $this->postJson('/platno/pages', ['title' => 'Duplicate', 'slug' => 'same', 'text' => 'Replace'])->assertUnprocessable();
        $this->postJson('/platno/pages', ['title' => 'Bad', 'slug' => '../escape', 'text' => 'Bad'])->assertUnprocessable();
        $this->putJson('/platno/pages/1', ['revision' => 1, 'title' => 'Bad', 'text' => ['unexpected']])->assertUnprocessable();
        $this->putJson('/platno/pages/1', ['revision' => 1, 'title' => 'Missing document'])->assertUnprocessable();
        self::assertSame(1, Page::query()->count());
        self::assertSame('Keep', Page::query()->firstOrFail()->document['blocks'][0]['data']['text']);
        self::assertSame('Original', Page::query()->firstOrFail()->title);
        self::assertSame(1, Page::query()->firstOrFail()->revision);
    }

    // Prevent lossy replacement or accidental publication of unsupported stored schemas.
    public function test_unsupported_content_is_preserved_and_fails_closed_on_read_and_write(): void
    {
        $this->install();
        $this->authorizeEditor();
        $document = ['version' => 99, 'blocks' => [['type' => 'unknown', 'version' => 5, 'data' => ['future' => 'retain']]]];
        $page = Page::query()->create(['slug' => 'future', 'title' => 'Future', 'document' => $document]);
        $publication = Publication::query()->create(['page_id' => $page->getKey(), 'slug' => 'future', 'title' => 'Future', 'document' => $document, 'source_revision' => 1]);
        $page->update(['publication_id' => $publication->getKey()]);

        $this->get('/platno/pages/'.$page->getKey().'/edit')->assertUnprocessable();
        $this->get('/platno/pages/'.$page->getKey().'/preview')->assertUnprocessable();
        $this->get('/platno/pages/'.$page->getKey().'/history/'.$publication->getKey())->assertUnprocessable();
        $this->put('/platno/pages/'.$page->getKey(), ['revision' => 1, 'title' => 'Replace', 'text' => 'Lost'])->assertUnprocessable();
        $this->post('/platno/pages/'.$page->getKey().'/publish', ['revision' => 1])->assertUnprocessable();
        $this->get('/pages/future')->assertStatus(503)->assertDontSee('retain');
        self::assertSame($document, $page->fresh()->document);
        self::assertSame(1, $page->fresh()->revision);
        self::assertSame(1, Publication::query()->count());
    }

    // Prevent unknown plugin fields or schema versions being silently discarded on persistence.
    public function test_document_writes_reject_unknown_fields_types_and_versions(): void
    {
        $this->install();
        $documents = $this->app->make(DocumentValidator::class);
        $valid = $documents->text('Known');
        $invalidDocuments = [
            array_replace($valid, ['unknown' => true]),
            ['version' => 1, 'blocks' => [['type' => 'missing', 'version' => 1, 'data' => ['text' => 'Keep']]]],
            ['version' => 1, 'blocks' => [['type' => 'text', 'version' => 2, 'data' => ['text' => 'Keep']]]],
            ['version' => 1, 'blocks' => [['type' => 'text', 'version' => 1, 'data' => ['text' => 'Keep', 'unknown' => 'Retain']]]],
        ];

        foreach ($invalidDocuments as $document) {
            try {
                $this->app->make(DraftManager::class)->create('invalid', 'Invalid', $document);
                self::fail('Unsupported content must fail validation.');
            } catch (ValidationException) {
                self::assertSame(0, Page::query()->count());
            }
        }
    }
}
