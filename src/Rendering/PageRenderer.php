<?php

declare(strict_types=1);

namespace Platno\Rendering;

use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Platno\Documents\DocumentValidator;
use Platno\Models\Page;
use Platno\Models\Publication;

final class PageRenderer
{
    public function __construct(private readonly DocumentValidator $documents, private readonly BlockRenderer $blocks, private readonly Theme $theme) {}

    public function render(Page|Publication $content, bool $preview = false): Response
    {
        try {
            $document = $this->documents->validate($content->document);
        } catch (ValidationException $exception) {
            if (! $preview) {
                throw $exception;
            }

            return response()->view('platno::editor.unsupported', ['page' => $content], 422)->header('Cache-Control', 'private, no-store');
        }
        $blocks = array_map(fn (array $block): string => $this->blocks->render($block['type'], $block['data'], $preview), $document['blocks']);

        return response()->view('platno::public.page', ['publication' => $content, 'blocks' => $blocks, 'preview' => $preview, 'theme' => $this->theme->tokens()])->header('Cache-Control', 'private, no-store');
    }
}
