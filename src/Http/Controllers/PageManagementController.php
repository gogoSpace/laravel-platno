<?php

declare(strict_types=1);

namespace Platno\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Platno\Models\Page;
use Platno\Models\Publication;
use Platno\Publishing\PageLifecycle;
use Platno\Publishing\RevisionConflict;
use Platno\Rendering\PageRenderer;

final class PageManagementController
{
    public function preview(string $page, PageRenderer $renderer): Response
    {
        return $renderer->render(Page::query()->findOrFail($page), true);
    }

    public function history(string $page): Response
    {
        return response()->view('platno::editor.history', ['page' => Page::query()->findOrFail($page), 'publications' => Publication::query()->where('page_id', $page)->select(['id', 'title', 'source_revision', 'created_at'])->orderByDesc('id')->simplePaginate(20)]);
    }

    public function publication(string $page, string $publication, PageRenderer $renderer): Response
    {
        return $renderer->render(Publication::query()->where('page_id', $page)->findOrFail($publication), true);
    }

    public function change(Request $request, PageLifecycle $lifecycle): RedirectResponse|Response
    {
        $page = $request->route('page');
        $operation = $request->route('operation');
        $publication = $request->route('publication');
        $current = Page::query()->findOrFail($page);
        $input = $request->validate(['revision' => ['required', 'integer', 'min:1']]);
        try {
            match ($operation) {
                'unpublish' => $lifecycle->unpublish((int) $page, (int) $input['revision']),
                'archive' => $lifecycle->archive((int) $page, (int) $input['revision']),
                'unarchive' => $lifecycle->unarchive((int) $page, (int) $input['revision']),
                'restore' => $lifecycle->restore((int) $page, (int) $input['revision'], (int) $publication),
            };
        } catch (RevisionConflict) {
            return response()->view('platno::editor.conflict', ['page' => $current->fresh(), 'submitted' => null], 409);
        }

        return redirect()->route('platno.pages.edit', $page)->with('platno.status', __('platno::editor.'.$operation.'_done'));
    }
}
