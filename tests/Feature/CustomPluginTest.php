<?php

declare(strict_types=1);

namespace Platno\Tests\Feature;

use Illuminate\Foundation\Application;
use Platno\Rendering\BlockRenderer;
use Platno\Tests\Fixtures\CalloutPlugin;
use Platno\Tests\TestCase;

final class CustomPluginTest extends TestCase
{
    /** @param Application $application */
    protected function defineEnvironment($application): void
    {
        parent::defineEnvironment($application);

        $application['config']->set('platno.plugins', [CalloutPlugin::class]);
        $application['view']->addNamespace('fixture', __DIR__.'/../Fixtures/views');
    }

    public function test_a_host_can_supply_a_plugin_and_view_through_configuration(): void
    {
        $output = $this->app->make(BlockRenderer::class)->render('example.callout', [
            'message' => 'Content provided by the host application',
        ]);

        $this->assertStringContainsString('Content provided by the host application', $output);
    }
}
