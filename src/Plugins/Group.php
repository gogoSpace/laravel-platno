<?php

declare(strict_types=1);

namespace Platno\Plugins;

final class Group extends Plugin
{
    public function type(): string
    {
        return 'group';
    }

    public function label(): string
    {
        return __('platno::editor.group');
    }

    public function fields(): array
    {
        return ['children' => ['type' => 'blocks', 'label' => __('platno::editor.child_blocks')], 'tone' => ['type' => 'select', 'label' => __('platno::editor.tone'), 'options' => ['plain' => __('platno::editor.plain'), 'muted' => __('platno::editor.muted')]]];
    }

    public function view(): string
    {
        return 'platno::plugins.group';
    }
}
