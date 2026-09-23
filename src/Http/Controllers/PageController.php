<?php

declare(strict_types=1);

namespace Platno\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Platno\Documents\DocumentValidator;
use Platno\Editing\DocumentInput;
use Platno\Editing\InspectorSchema;
use Platno\Models\Page;
use Platno\Publishing\DraftManager;
use Platno\Publishing\PagePublisher;
use Platno\Publishing\RevisionConflict;

final class PageController
{
    public function __construct(private readonly DocumentValidator $documents, private readonly DocumentInput $input, private readonly InspectorSchema $inspectors) {}

    public function index(Request $request): Response
    {
        $input = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', 'in:active,draft,published,archived']]);
        $query = Page::query()->orderByDesc('updated_at')->orderByDesc('id');
        ($input['status'] ?? '') === 'archived' ? $query->whereNotNull('archived_at') : $query->whereNull('archived_at');
        if (($input['status'] ?? '') === 'draft') {
            $query->whereNull('publication_id');
        }
        if (($input['status'] ?? '') === 'published') {
            $query->whereNotNull('publication_id');
        }
        if ($input['search'] ?? '') {
            $query->where(fn ($query) => $query->where('title', 'like', '%'.$input['search'].'%')->orWhere('slug', 'like', '%'.$input['search'].'%'));
        }

        return response()->view('platno::editor.index', ['pages' => $query->simplePaginate(20)->withQueryString()]);
    }

    public function create(): Response
    {
        return response()->view('platno::editor.page', ['page' => null, 'document' => ['version' => 1, 'blocks' => []], 'catalog' => $this->inspectors->catalog()]);
    }

    public function store(Request $request, DraftManager $drafts): RedirectResponse
    {
        $input = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['required', 'string', 'max:120'],
        ]);
        $page = $drafts->create($input['slug'], $input['title'], $this->input->read($request));

        return redirect()->route('platno.pages.edit', $page->getKey())->with('platno.status', __('platno::editor.saved'));
    }

    public function edit(string $page): Response
    {
        return $this->editor(Page::query()->with('publication')->findOrFail($page));
    }

    public function update(Request $request, string $page, DraftManager $drafts): RedirectResponse|Response
    {
        $current = Page::query()->findOrFail($page);
        try {
            $this->documents->validate($current->document);
        } catch (ValidationException) {
            return $this->editor($current);
        }

        $input = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'revision' => ['required', 'integer', 'min:1'],
        ]);
        $document = $this->input->read($request, $current->document);

        try {
            $drafts->save((int) $page, (int) $input['revision'], $input['title'], $document);
        } catch (RevisionConflict) {
            return response()->view('platno::editor.conflict', ['page' => $current->fresh(), 'submitted' => [...$input, 'document' => $document, 'text' => $request->input('text')]], 409);
        }

        return redirect()->route('platno.pages.edit', $page)->with('platno.status', __('platno::editor.saved'));
    }

    public function publish(Request $request, string $page, PagePublisher $publisher): RedirectResponse|Response
    {
        $current = Page::query()->findOrFail($page);
        $input = $request->validate(['revision' => ['required', 'integer', 'min:1']]);

        try {
            $publisher->publish((int) $page, (int) $input['revision']);
        } catch (RevisionConflict) {
            return response()->view('platno::editor.conflict', ['page' => $current->fresh(), 'submitted' => null], 409);
        } catch (ValidationException) {
            return response()->view('platno::editor.unsupported', ['page' => $current], 422);
        }

        return redirect()->route('platno.pages.edit', $page)->with('platno.status', __('platno::editor.published'));
    }

    private function editor(Page $page): Response
    {
        if ($page->archived_at !== null) {
            return response()->view('platno::editor.archived', ['page' => $page]);
        }
        try {
            $document = $this->documents->validate($page->document);
        } catch (ValidationException) {
            return response()->view('platno::editor.unsupported', ['page' => $page], 422);
        }

        return response()->view('platno::editor.page', ['page' => $page, 'document' => $document, 'catalog' => $this->inspectors->catalog()]);
    }
}
