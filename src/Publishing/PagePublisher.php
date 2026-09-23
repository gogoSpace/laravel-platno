<?php

declare(strict_types=1);

namespace Platno\Publishing;

use Illuminate\Database\DatabaseManager;
use Platno\Assets\AssetReferences;
use Platno\Documents\DocumentValidator;
use Platno\Models\Page;
use Platno\Models\Publication;

final class PagePublisher
{
    public function __construct(private readonly DatabaseManager $database, private readonly DocumentValidator $documents, private readonly AssetReferences $references) {}

    public function publish(int $pageIdentifier, int $expectedRevision): Publication
    {
        return $this->database->connection()->transaction(function () use ($pageIdentifier, $expectedRevision): Publication {
            // The conditional write serializes publishers and writers on every supported engine.
            $affected = Page::query()->whereKey($pageIdentifier)->where('revision', $expectedRevision)->whereNull('archived_at')->update([
                'revision' => $expectedRevision + 1,
                'updated_at' => now(),
            ]);

            if ($affected !== 1) {
                throw new RevisionConflict;
            }

            $page = Page::query()->findOrFail($pageIdentifier);
            $document = $this->documents->validate($page->document);
            $publication = Publication::query()->create([
                'page_id' => $page->getKey(),
                'slug' => $page->slug,
                'title' => $page->title,
                'document' => $document,
                'source_revision' => $expectedRevision,
            ]);

            $this->references->synchronize('publication', $publication->getKey(), $document);
            $page->publication_id = $publication->getKey();
            $page->save();

            return $publication;
        });
    }
}
