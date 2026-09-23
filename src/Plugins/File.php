<?php

declare(strict_types=1);

namespace Platno\Plugins;

final class File extends Plugin
{
    public function type(): string
    {
        return 'file';
    }

    public function label(): string
    {
        return __('platno::editor.file');
    }

    public function fields(): array
    {
        return ['asset' => ['type' => 'asset', 'kind' => 'file', 'label' => __('platno::editor.file'), 'required' => true], 'label' => ['type' => 'text', 'label' => __('platno::editor.link_label'), 'default' => 'Download file', 'required' => true, 'max' => 200]];
    }

    public function view(): string
    {
        return 'platno::plugins.file';
    }
}
