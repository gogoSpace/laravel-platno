<?php

declare(strict_types=1);

namespace Platno\Tests\Unit;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Platno\Plugins\PluginRegistry;
use Platno\Plugins\Text;

final class PluginRegistryTest extends TestCase
{
    public function test_registration_cannot_silently_replace_an_existing_plugin(): void
    {
        $registry = new PluginRegistry;
        $registry->register(new Text);

        $this->expectException(LogicException::class);

        $registry->register(new Text);
    }

    public function test_unknown_plugin_types_fail_instead_of_using_an_unrelated_renderer(): void
    {
        $registry = new PluginRegistry;

        $this->expectException(InvalidArgumentException::class);

        $registry->get('missing.plugin');
    }
}
