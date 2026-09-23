<?php

declare(strict_types=1);

namespace Platno\Tests\Feature;

use Illuminate\Foundation\Application;
use Platno\Models\Page;
use Platno\Models\Publication;
use Platno\Rendering\BlockRenderer;
use Platno\Tests\Fixtures\NoticePlugin;
use Platno\Tests\PersistenceTestCase;

final class CanvasTest extends PersistenceTestCase
{
    /** @param Application $application */
    protected function defineEnvironment($application): void
    {
        parent::defineEnvironment($application);
        $application['config']->set('platno.plugins', [...config('platno.plugins'), NoticePlugin::class]);
        $application['view']->addNamespace('fixture', __DIR__.'/../Fixtures/views');
    }

    public function test_canvas_uses_plugin_views_without_saving_or_publishing_and_inherits_host_access(): void
    {
        $this->install();
        $this->postJson('/platno/canvas', [])->assertForbidden();
        $this->authorizeEditor();
        $text = ['type' => 'text', 'version' => 1, 'data' => ['text' => '  <script>literal</script>  ']];
        $document = ['version' => 1, 'blocks' => [
            ['type' => 'columns', 'version' => 1, 'data' => ['left' => [$text], 'right' => [$text]]],
            ['type' => 'example.notice', 'version' => 1, 'data' => ['message' => 'Host plugin content', 'tone' => 'note']],
        ]];
        $response = $this->postJson('/platno/canvas', ['document' => $document])->assertOk();
        $output = $response->json('html');
        self::assertStringContainsString('Host plugin content', $output);
        self::assertStringContainsString('document.blocks.0.data.left.0', $output);
        self::assertStringContainsString('document.blocks.0.data.right.0', $output);
        self::assertStringNotContainsString('<script>', $output);
        self::assertStringContainsString('&lt;script&gt;literal&lt;/script&gt;', $output);
        $public = app(BlockRenderer::class)->render('columns', $document['blocks'][0]['data']);
        self::assertStringNotContainsString('data-platno-path', $public);
        self::assertStringContainsString('&lt;script&gt;literal&lt;/script&gt;', $public);
        self::assertSame(0, Page::query()->count());
        self::assertSame(0, Publication::query()->count());
        $this->postJson('/platno/canvas', ['document' => ['version' => 1, 'blocks' => [['type' => 'missing', 'version' => 1, 'data' => []]]]])->assertUnprocessable();
        $this->postJson('/platno/canvas', ['document' => ['version' => 1, 'blocks' => [['type' => 'image', 'version' => 1, 'data' => ['asset' => '', 'alt' => '', 'caption' => '']]]]])->assertUnprocessable();
        self::assertSame(0, Page::query()->count());
    }
}
