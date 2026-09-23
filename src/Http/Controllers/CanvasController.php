<?php

declare(strict_types=1);

namespace Platno\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Platno\Editing\DocumentInput;
use Platno\Rendering\BlockRenderer;
use Platno\Rendering\Theme;

final class CanvasController
{
    public function __invoke(Request $request, DocumentInput $input, BlockRenderer $renderer, Theme $theme): JsonResponse
    {
        $document = $input->read($request);
        $output = '';
        foreach ($document['blocks'] as $position => $block) {
            $output .= $renderer->render($block['type'], $block['data'], true, 'document.blocks.'.$position);
        }

        return response()->json([
            'html' => $output,
            'styles' => view('platno::public.styles', ['theme' => $theme->tokens()])->render(),
        ])->header('Cache-Control', 'private, no-store');
    }
}
