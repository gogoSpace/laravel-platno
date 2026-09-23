<?php

declare(strict_types=1);

namespace Platno\Publishing;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;
use Platno\Assets\AssetReferences;
use Platno\Documents\DocumentValidator;
use Platno\Models\Page;

final class DraftManager
{
    public function __construct(
        private readonly DatabaseManager $database,
        private readonly Factory $validation,
        private readonly DocumentValidator $documents,
        private readonly AssetReferences $references,
    ) {}

    /** @param array<string, mixed> $document */
    public function create(string $slug, string $title, array $document): Page
    {
        $this->validation->make(['slug' => $slug, 'title' => $title], [
            'slug' => ['required', 'string', 'max:120', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/'],
            'title' => ['required', 'string', 'max:200'],
        ])->validate();
        $document = $this->documents->validate($document);

        try {
            return $this->database->connection()->transaction(function () use ($slug, $title, $document): Page {
                $page = Page::query()->create(['slug' => $slug, 'title' => $title, 'document' => $document, 'revision' => 1]);
                $this->references->synchronize('draft', $page->getKey(), $document);

                return $page;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['slug' => __('platno::editor.slug_taken')]);
        }
    }

    /** @param array<string, mixed> $document */
    public function save(int $pageIdentifier, int $expectedRevision, string $title, array $document): Page
    {
        $this->validation->make(['title' => $title], ['title' => ['required', 'string', 'max:200']])->validate();
        $document = $this->documents->validate($document);

        return $this->database->connection()->transaction(function () use ($pageIdentifier, $expectedRevision, $title, $document): Page {
            $affected = Page::query()->whereKey($pageIdentifier)->where('revision', $expectedRevision)->whereNull('archived_at')->update([
                'revision' => $expectedRevision + 1,
                'updated_at' => now(),
            ]);

            if ($affected !== 1) {
                throw new RevisionConflict;
            }

            $page = Page::query()->findOrFail($pageIdentifier);
            $this->documents->validate($page->document);
            $this->references->synchronize('draft', $pageIdentifier, $document);
            $page->title = $title;
            $page->document = $document;
            $page->save();

            return $page;
        });
    }
}
