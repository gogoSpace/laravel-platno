<?php

declare(strict_types=1);

namespace Platno\Plugins;

use InvalidArgumentException;
use LogicException;
use Platno\Contracts\BlockPlugin;

final class PluginRegistry
{
    /** @var array<string, BlockPlugin> */
    private array $plugins = [];

    public function register(BlockPlugin $plugin): void
    {
        $type = $plugin->type();

        if (preg_match('/\A[a-z][a-z0-9]*(?:[._-][a-z0-9]+)*\z/', $type) !== 1) {
            throw new InvalidArgumentException("Invalid plugin type [{$type}].");
        }

        if (array_key_exists($type, $this->plugins)) {
            throw new LogicException("Plugin type [{$type}] is already registered.");
        }

        $this->plugins[$type] = $plugin;
    }

    public function get(string $type): BlockPlugin
    {
        return $this->plugins[$type]
            ?? throw new InvalidArgumentException("Unknown plugin type [{$type}].");
    }

    /** @return array<string, BlockPlugin> */
    public function all(): array
    {
        return $this->plugins;
    }
}
