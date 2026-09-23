<?php

declare(strict_types=1);

namespace Platno\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Platno\Models\Page;
use Platno\Rendering\PageRenderer;

final class PublicPageController
{
    public function __invoke(string $slug, PageRenderer $renderer): Response
    {
        $page = Page::query()->with('publication')->where('slug', $slug)->whereNotNull('publication_id')->firstOrFail();
        $publication = $page->publication;
        abort_unless($publication !== null && $publication->page_id === $page->getKey(), 404);

        try {
            return $renderer->render($publication);
        } catch (ValidationException) {
            return response()->view('platno::public.unavailable', [], 503)->header('Cache-Control', 'no-store');
        }

    }
}
