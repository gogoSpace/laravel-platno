<?php

declare(strict_types=1);

namespace Platno\Plugins;

final class Columns extends Plugin
{
    public function type(): string
    {
        return 'columns';
    }

    public function label(): string
    {
        return __('platno::editor.columns');
    }

    public function fields(): array
    {
        return ['left' => ['type' => 'blocks', 'label' => __('platno::editor.left_column')], 'right' => ['type' => 'blocks', 'label' => __('platno::editor.right_column')]];
    }

    public function view(): string
    {
        return 'platno::plugins.columns';
    }
}
