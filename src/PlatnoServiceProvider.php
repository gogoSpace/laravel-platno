<?php

declare(strict_types=1);

namespace Platno;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Platno\Console\InstallCommand;
use Platno\Console\MakePluginCommand;
use Platno\Plugins\PluginRegistry;

final class PlatnoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/platno.php', 'platno');

        if (! $this->app['config']->has('filesystems.disks.platno')) {
            $this->app['config']->set('filesystems.disks.platno', ['driver' => 'local', 'root' => storage_path('app/platno'), 'visibility' => 'private', 'throw' => true, 'serve' => false]);
        }

        $this->app->singleton(PluginRegistry::class, function (Application $application): PluginRegistry {
            $pluginClasses = $application->make('config')->get('platno.plugins', []);

            if (! is_array($pluginClasses)) {
                throw new InvalidArgumentException('The platno.plugins configuration must be a list of plugin classes.');
            }

            $registry = new PluginRegistry;

            foreach ($pluginClasses as $pluginClass) {
                $registry->register($application->make($pluginClass));
            }

            return $registry;
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'platno');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'platno');

        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class, MakePluginCommand::class]);
            $this->publishes([
                __DIR__.'/../config/platno.php' => config_path('platno.php'),
            ], 'platno-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/platno'),
            ], 'platno-views');
        }
    }
}
