<?php

declare(strict_types=1);

namespace Platno\Plugins;

final class Link extends Plugin
{
    public function type(): string
    {
        return 'link';
    }

    public function label(): string
    {
        return __('platno::editor.link_block');
    }

    public function fields(): array
    {
        return ['label' => ['type' => 'text', 'label' => __('platno::editor.link_label'), 'default' => 'Read more', 'required' => true, 'max' => 200], 'url' => ['type' => 'url', 'label' => __('platno::editor.link_url'), 'default' => '/', 'required' => true], 'style' => ['type' => 'select', 'label' => __('platno::editor.link_style'), 'options' => ['link' => __('platno::editor.link'), 'button' => __('platno::editor.button')]]];
    }

    public function view(): string
    {
        return 'platno::plugins.link';
    }
}
