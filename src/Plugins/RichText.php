<?php

declare(strict_types=1);

namespace Platno\Plugins;

final class RichText extends Plugin
{
    public function type(): string
    {
        return 'rich-text';
    }

    public function label(): string
    {
        return __('platno::editor.rich_text');
    }

    public function fields(): array
    {
        return ['content' => ['type' => 'richtext', 'label' => __('platno::editor.rich_text'), 'default' => [['type' => 'paragraph', 'runs' => [['text' => '', 'bold' => false, 'italic' => false, 'link' => '']]]]]];
    }

    public function view(): string
    {
        return 'platno::plugins.rich-text';
    }
}
