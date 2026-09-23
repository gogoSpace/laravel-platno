<?php

declare(strict_types=1);

namespace Platno\Plugins;

final class Image extends Plugin
{
    public function type(): string
    {
        return 'image';
    }

    public function label(): string
    {
        return __('platno::editor.image');
    }

    public function fields(): array
    {
        return ['asset' => ['type' => 'asset', 'kind' => 'image', 'label' => __('platno::editor.image'), 'required' => true], 'alt' => ['type' => 'text', 'label' => __('platno::editor.image_alt'), 'max' => 500], 'caption' => ['type' => 'text', 'label' => __('platno::editor.caption'), 'max' => 1000]];
    }

    public function view(): string
    {
        return 'platno::plugins.image';
    }
}
