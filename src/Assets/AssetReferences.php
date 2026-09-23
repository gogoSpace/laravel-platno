<?php

declare(strict_types=1);

namespace Platno\Assets;

use Illuminate\Database\DatabaseManager;
use Illuminate\Validation\ValidationException;
use Platno\Contracts\EditablePlugin;
use Platno\Models\Asset;
use Platno\Plugins\PluginRegistry;

final class AssetReferences
{
    public function __construct(private readonly DatabaseManager $database, private readonly PluginRegistry $plugins) {}

    /** Must run inside the owning draft/publication transaction.
     * @param  array<string, mixed>  $document
     */
    public function synchronize(string $ownerType, int $ownerIdentifier, array $document): void
    {
        $required = [];
        $this->collect($document['blocks'], $required);
        $identifiers = array_keys($required);
        sort($identifiers);
        $assets = Asset::query()->whereIn('id', $identifiers)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        foreach ($required as $identifier => $requiresImage) {
            $asset = $assets->get($identifier);
            if ($asset === null || $asset->state !== 'active' || ($requiresImage && ! $asset->isImage())) {
                throw ValidationException::withMessages(['document' => __('platno::editor.asset_unavailable')]);
            }
        }
        $references = $this->database->table('platno_asset_references');
        $references->where('owner_type', $ownerType)->where('owner_id', $ownerIdentifier)->delete();
        foreach ($identifiers as $identifier) {
            $this->database->table('platno_asset_references')->insert(['asset_id' => $identifier, 'owner_type' => $ownerType, 'owner_id' => $ownerIdentifier]);
        }
    }

    /** @param list<array<string, mixed>> $blocks
     * @param  array<string, bool>  $required
     */
    private function collect(array $blocks, array &$required): void
    {
        foreach ($blocks as $block) {
            $plugin = $this->plugins->get($block['type']);
            if (! $plugin instanceof EditablePlugin) {
                continue;
            }
            foreach ($plugin->fields() as $name => $field) {
                if ($field['type'] === 'blocks') {
                    $this->collect($block['data'][$name], $required);
                } elseif ($field['type'] === 'asset' && $block['data'][$name] !== '') {
                    $identifier = $block['data'][$name];
                    $required[$identifier] = ($required[$identifier] ?? false) || ($field['kind'] ?? 'file') === 'image';
                }
            }
        }
    }

    public function isPublic(string $identifier): bool
    {
        return $this->database->table('platno_asset_references as reference')
            ->join('platno_pages as page', 'page.publication_id', '=', 'reference.owner_id')
            ->where('reference.owner_type', 'publication')->where('reference.asset_id', $identifier)->exists();
    }
}
