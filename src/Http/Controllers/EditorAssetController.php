<?php

declare(strict_types=1);

namespace Platno\Http\Controllers;

use Illuminate\Http\Response;

final class EditorAssetController
{
    public const SCRIPTS = ['editor', 'editor-store', 'editor-canvas', 'editor-inspector', 'editor-outline', 'editor-dom', 'workspace', 'rich-text', 'asset-picker'];

    public function __invoke(string $script): Response
    {
        abort_unless(in_array($script, self::SCRIPTS, true), 404);

        return response(file_get_contents(__DIR__.'/../../../resources/js/'.$script.'.js'), 200, ['Content-Type' => 'text/javascript; charset=utf-8']);
    }
}
