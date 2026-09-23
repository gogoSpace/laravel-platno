<?php

declare(strict_types=1);

namespace Platno\Editing;

use LogicException;
use Platno\Contracts\EditablePlugin;
use Platno\Plugins\PluginRegistry;

final class InspectorSchema
{
    public function __construct(private readonly PluginRegistry $plugins) {}

    /** @return list<array<string, mixed>> */
    public function catalog(): array
    {
        $catalog = [];

        foreach ($this->plugins->all() as $plugin) {
            $fields = $plugin instanceof EditablePlugin ? $plugin->fields() : [];

            foreach ($fields as $name => $field) {
                if (preg_match('/\A[a-zA-Z][a-zA-Z0-9_]*\z/', $name) !== 1 || ! isset($field['label']) || ! in_array($field['type'] ?? null, ['text', 'textarea', 'number', 'checkbox', 'select', 'url', 'blocks', 'richtext', 'asset'], true)) {
                    throw new LogicException("Invalid inspector field [{$plugin->type()}.{$name}].");
                }
            }

            $catalog[] = [
                'type' => $plugin->type(),
                'version' => $plugin->schemaVersion(),
                'label' => $plugin instanceof EditablePlugin ? $plugin->label() : $plugin->type(),
                'defaults' => $plugin->defaults(),
                'fields' => $fields,
                'editable' => $plugin instanceof EditablePlugin,
            ];
        }

        return $catalog;
    }
}
