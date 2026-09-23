<?php

declare(strict_types=1);

namespace Platno\Documents;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Platno\Contracts\EditablePlugin;
use Platno\Plugins\PluginRegistry;

final class DocumentValidator
{
    public function __construct(private readonly Factory $validation, private readonly PluginRegistry $plugins) {}

    /** @param array<string, mixed> $document
     * @return array<string, mixed>
     */
    public function validate(array $document): array
    {
        $this->validation->make(['document' => $document], [
            'document' => ['required', 'array:version,blocks'],
            'document.version' => ['required', 'integer', 'in:1'],
            'document.blocks' => ['present', 'array', 'list', 'max:100'],
            'document.blocks.*' => ['required', 'array:id,type,version,data'],
            'document.blocks.*.id' => ['sometimes', 'uuid', 'distinct'],
            'document.blocks.*.type' => ['required', 'string'],
            'document.blocks.*.version' => ['required', 'integer'],
            'document.blocks.*.data' => ['present', 'array'],
        ])->validate();

        if ($document['version'] !== 1) {
            throw ValidationException::withMessages(['document' => __('platno::editor.unsupported_document')]);
        }

        $identities = [];
        $total = 0;
        $this->validateBlocks($document['blocks'], 'document.blocks', 1, $identities, $total);

        return $document;
    }

    /** @param list<array<string, mixed>> $blocks
     * @param  array<string, bool>  $identities
     */
    private function validateBlocks(array $blocks, string $path, int $depth, array &$identities, int &$total): void
    {
        if (($depth > 6 && $blocks !== []) || ($total += count($blocks)) > 100) {
            throw ValidationException::withMessages([$path => __('platno::editor.nested_limit')]);
        }

        $this->validation->make(['blocks' => $blocks], [
            'blocks' => ['present', 'array', 'list', 'max:100'],
            'blocks.*' => ['required', 'array:id,type,version,data'],
            'blocks.*.id' => ['sometimes', 'uuid'],
            'blocks.*.type' => ['required', 'string'],
            'blocks.*.version' => ['required', 'integer'],
            'blocks.*.data' => ['present', 'array'],
        ])->validate();

        foreach ($blocks as $position => $block) {
            $blockPath = $path.'.'.$position;
            if (isset($block['id'])) {
                $identity = strtolower($block['id']);
                if (isset($identities[$identity])) {
                    throw ValidationException::withMessages([$blockPath => __('platno::editor.duplicate_identity')]);
                }
                $identities[$identity] = true;
            }
            try {
                $plugin = $this->plugins->get($block['type']);
            } catch (InvalidArgumentException) {
                throw ValidationException::withMessages([$blockPath => __('platno::editor.unsupported_document')]);
            }

            if ($block['version'] !== $plugin->schemaVersion()) {
                throw ValidationException::withMessages([$blockPath => __('platno::editor.unsupported_document')]);
            }

            $validator = $this->validation->make($block['data'], $plugin->rules());
            if ($validator->fails()) {
                $messages = [];
                foreach ($validator->errors()->messages() as $field => $errors) {
                    $messages[$blockPath.'.data.'.$field] = $errors;
                }
                throw ValidationException::withMessages($messages);
            }
            if ($validator->validated() != $block['data']) {
                throw ValidationException::withMessages([$blockPath => __('platno::editor.unknown_data')]);
            }
            if ($plugin instanceof EditablePlugin) {
                foreach ($plugin->fields() as $name => $field) {
                    if ($field['type'] === 'blocks') {
                        $this->validateBlocks($block['data'][$name], $blockPath.'.data.'.$name, $depth + 1, $identities, $total);
                    }
                }
            }
        }
    }

    /** @return array{version: int, blocks: list<array{type: string, version: int, data: array{text: string}}> } */
    public function text(string $text): array
    {
        return ['version' => 1, 'blocks' => [['type' => 'text', 'version' => 1, 'data' => ['text' => $text]]]];
    }

    /** @param array<string, mixed> $document */
    public function editableText(array $document): string
    {
        $validated = $this->validate($document);

        if (count($validated['blocks']) !== 1 || $validated['blocks'][0]['type'] !== 'text') {
            throw ValidationException::withMessages(['document' => __('platno::editor.unsupported_document')]);
        }

        return $validated['blocks'][0]['data']['text'];
    }
}
