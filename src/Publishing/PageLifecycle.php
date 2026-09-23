<?php

declare(strict_types=1);

namespace Platno\Publishing;

use Illuminate\Database\DatabaseManager;
use Platno\Models\Page;
use Platno\Models\Publication;

final class PageLifecycle
{
    public function __construct(private readonly DatabaseManager $database, private readonly DraftManager $drafts) {}

    public function unpublish(int $pageIdentifier, int $expectedRevision): Page
    {
        return $this->changeState($pageIdentifier, $expectedRevision, ['publication_id' => null]);
    }

    public function archive(int $pageIdentifier, int $expectedRevision): Page
    {
        return $this->changeState($pageIdentifier, $expectedRevision, ['publication_id' => null, 'archived_at' => now()]);
    }

    public function unarchive(int $pageIdentifier, int $expectedRevision): Page
    {
        return $this->changeState($pageIdentifier, $expectedRevision, ['archived_at' => null]);
    }

    public function restore(int $pageIdentifier, int $expectedRevision, int $publicationIdentifier): Page
    {
        $publication = Publication::query()->where('page_id', $pageIdentifier)->findOrFail($publicationIdentifier);

        return $this->drafts->save($pageIdentifier, $expectedRevision, $publication->title, $publication->document);
    }

    /** @param array<string, mixed> $changes */
    private function changeState(int $pageIdentifier, int $expectedRevision, array $changes): Page
    {
        return $this->database->connection()->transaction(function () use ($pageIdentifier, $expectedRevision, $changes): Page {
            if (Page::query()->whereKey($pageIdentifier)->where('revision', $expectedRevision)->update([...$changes, 'revision' => $expectedRevision + 1, 'updated_at' => now()]) !== 1) {
                throw new RevisionConflict;
            }

            return Page::query()->findOrFail($pageIdentifier);
        });
    }
}
