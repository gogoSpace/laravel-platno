<?php

declare(strict_types=1);

namespace Platno\Plugins;

use Illuminate\Validation\Rule;
use Platno\Contracts\EditablePlugin;
use Platno\Validation\RichText;
use Platno\Validation\SafeLink;

abstract class Plugin implements EditablePlugin
{
    public function schemaVersion(): int
    {
        return 1;
    }

    public function defaults(): array
    {
        return array_map(fn (array $field): mixed => $field['default'] ?? match ($field['type']) {
            'checkbox' => false,
            'number' => 0,
            'blocks', 'richtext' => [],
            'select' => (string) array_key_first($field['options']),
            default => '',
        }, $this->fields());
    }

    public function rules(): array
    {
        $rules = [];

        foreach ($this->fields() as $name => $field) {
            $rules[$name] = [$field['required'] ?? false ? 'required' : 'present'];
            $rules[$name] = [...$rules[$name], ...match ($field['type']) {
                'number' => ['numeric', 'min:'.($field['min'] ?? -1000000), 'max:'.($field['max'] ?? 1000000)],
                'checkbox' => ['boolean'],
                'select' => [Rule::in(array_keys($field['options']))],
                'url' => ['string', 'max:2048', new SafeLink],
                'asset' => ['string', 'uuid'],
                'blocks' => ['array', 'list', 'max:100'],
                'richtext' => ['array', new RichText],
                default => ['string', 'max:'.($field['max'] ?? 50000)],
            }];
        }

        return $rules;
    }
}
