<?php

declare(strict_types=1);

namespace Platno\Plugins;

final class Heading extends Plugin
{
    public function type(): string
    {
        return 'heading';
    }

    public function label(): string
    {
        return __('platno::editor.heading');
    }

    public function fields(): array
    {
        return ['text' => ['type' => 'text', 'label' => __('platno::editor.heading_text'), 'required' => true, 'default' => 'Heading', 'max' => 500], 'level' => ['type' => 'select', 'label' => __('platno::editor.heading_level'), 'options' => ['h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4']]];
    }

    public function view(): string
    {
        return 'platno::plugins.heading';
    }
}
