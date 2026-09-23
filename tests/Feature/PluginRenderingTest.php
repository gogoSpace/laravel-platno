<?php

declare(strict_types=1);

namespace Platno\Tests\Feature;

use Illuminate\Validation\ValidationException;
use Platno\Rendering\BlockRenderer;
use Platno\Tests\TestCase;

final class PluginRenderingTest extends TestCase
{
    public function test_package_renders_plain_text_without_executing_embedded_html(): void
    {
        $output = $this->app->make(BlockRenderer::class)->render('text', [
            'text' => '<script>alert("unsafe")</script>',
        ]);

        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringContainsString('&lt;script&gt;', $output);
    }

    public function test_plugin_validation_rejects_invalid_content_before_rendering(): void
    {
        $this->expectException(ValidationException::class);

        $this->app->make(BlockRenderer::class)->render('text', ['text' => ['unexpected array']]);
    }
}
