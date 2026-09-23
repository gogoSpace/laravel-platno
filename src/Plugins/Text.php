<?php

declare(strict_types=1);

namespace Platno\Plugins;

final class Text extends Plugin
{
    public function type(): string
    {
        return 'text';
    }

    public function schemaVersion(): int
    {
        return 1;
    }

    public function label(): string
    {
        return __('platno::editor.text_block');
    }

    public function fields(): array
    {
        return ['text' => ['type' => 'textarea', 'label' => __('platno::editor.text'), 'help' => __('platno::editor.text_help'), 'max' => 50000]];
    }

    /** @return array{text: string} */
    public function defaults(): array
    {
        return ['text' => ''];
    }

    /** @return array{text: list<string>} */
    public function rules(): array
    {
        return ['text' => ['present', 'string', 'max:50000']];
    }

    public function view(): string
    {
        return 'platno::plugins.text';
    }
}
