<?php

declare(strict_types=1);

namespace Platno\Plugins;

final class Divider extends Plugin
{
    public function type(): string
    {
        return 'divider';
    }

    public function label(): string
    {
        return __('platno::editor.divider');
    }

    public function fields(): array
    {
        return [];
    }

    public function view(): string
    {
        return 'platno::plugins.divider';
    }
}
