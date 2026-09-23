<?php

declare(strict_types=1);

namespace Platno\Rendering;

use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Platno\Documents\DocumentValidator;
use Platno\Plugins\PluginRegistry;

final class BlockRenderer
{
    public function __construct(
        private readonly PluginRegistry $plugins,
        private readonly ValidationFactory $validation,
        private readonly ViewFactory $views,
        private readonly DocumentValidator $documents,
    ) {}

    /** @param array<string, mixed> $data */
    public function render(string $type, array $data, bool $preview = false, ?string $canvasPath = null): string
    {
        $plugin = $this->plugins->get($type);
        $validated = $this->validation->make(
            array_replace($plugin->defaults(), $data),
            $plugin->rules(),
        )->validate();

        $this->documents->validate(['version' => 1, 'blocks' => [['type' => $type, 'version' => $plugin->schemaVersion(), 'data' => $validated]]]);

        $output = $this->views->make($plugin->view(), ['data' => $validated, 'renderer' => $this, 'preview' => $preview, 'canvasPath' => $canvasPath])->render();

        return $canvasPath === null ? $output : $this->views->make('platno::editor.canvas-block', ['output' => $output, 'canvasPath' => $canvasPath, 'type' => $type])->render();
    }

    /** @param list<array<string, mixed>> $blocks */
    public function children(array $blocks, bool $preview, ?string $canvasPath, string $field): string
    {
        $output = '';
        foreach ($blocks as $position => $block) {
            $path = $canvasPath === null ? null : $canvasPath.'.data.'.$field.'.'.$position;
            $output .= $this->render($block['type'], $block['data'], $preview, $path);
        }

        return $output;
    }
}
