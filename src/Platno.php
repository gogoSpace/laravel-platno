<?php

declare(strict_types=1);

namespace Platno;

use Illuminate\Routing\Router;
use InvalidArgumentException;
use LogicException;
use Platno\Http\Controllers\AssetController;
use Platno\Http\Controllers\CanvasController;
use Platno\Http\Controllers\EditorAssetController;
use Platno\Http\Controllers\PageController;
use Platno\Http\Controllers\PageManagementController;
use Platno\Http\Controllers\PublicPageController;
use Platno\Http\Middleware\EditorHeaders;

final class Platno
{
    public static function editorRoutes(string $prefix = 'platno'): void
    {
        self::mount($prefix, 'platno.pages.', function (Router $router): void {
            $router->middleware(['web', EditorHeaders::class])->group(function (Router $router): void {
                foreach (EditorAssetController::SCRIPTS as $script) {
                    $name = $script === 'editor' ? 'script' : $script;
                    $router->get('assets/'.$script.'.js', EditorAssetController::class)->defaults('script', $script)->name('platno.editor.'.$name);
                }
                $router->post('canvas', CanvasController::class)->middleware('throttle:120,1,platno-canvas')->name('platno.editor.canvas');
                $router->middleware(['throttle:120,1,platno-editor'])->group(function (Router $router): void {
                    $router->get('media', [AssetController::class, 'index'])->name('platno.assets.index');
                    $router->post('media', [AssetController::class, 'store'])->name('platno.assets.store');
                    $router->get('media/{asset}', [AssetController::class, 'show'])->whereUuid('asset')->name('platno.assets.show');
                    $router->delete('media/{asset}', [AssetController::class, 'destroy'])->whereUuid('asset')->name('platno.assets.destroy');
                    $router->get('/', [PageController::class, 'index'])->name('platno.pages.index');
                    $router->get('pages/create', [PageController::class, 'create'])->name('platno.pages.create');
                    $router->post('pages', [PageController::class, 'store'])->name('platno.pages.store');
                    $router->get('pages/{page}/preview', [PageManagementController::class, 'preview'])->whereNumber('page')->name('platno.pages.preview');
                    $router->get('pages/{page}/history', [PageManagementController::class, 'history'])->whereNumber('page')->name('platno.pages.history');
                    $router->get('pages/{page}/history/{publication}', [PageManagementController::class, 'publication'])->whereNumber(['page', 'publication'])->name('platno.pages.publication');
                    foreach (['unpublish', 'archive', 'unarchive'] as $operation) {
                        $router->post('pages/{page}/'.$operation, [PageManagementController::class, 'change'])->whereNumber('page')->defaults('operation', $operation)->name('platno.pages.'.$operation);
                    }
                    $router->post('pages/{page}/history/{publication}/restore', [PageManagementController::class, 'change'])->whereNumber(['page', 'publication'])->defaults('operation', 'restore')->name('platno.pages.restore');
                    $router->get('pages/{page}/edit', [PageController::class, 'edit'])->whereNumber('page')->name('platno.pages.edit');
                    $router->put('pages/{page}', [PageController::class, 'update'])->whereNumber('page')->name('platno.pages.update');
                    $router->post('pages/{page}/publish', [PageController::class, 'publish'])->whereNumber('page')->name('platno.pages.publish');
                });
            });
        });
    }

    public static function publicRoutes(string $prefix = 'pages'): void
    {
        self::mount($prefix, 'platno.public.', function (Router $router): void {
            $router->get('media/{asset}', [AssetController::class, 'published'])->whereUuid('asset')->name('platno.public.asset');
            $router->get('{slug}', PublicPageController::class)
                ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')->name('platno.public.show');
        });
    }

    private static function mount(string $prefix, string $namePrefix, callable $register): void
    {
        if (preg_match('/\A[a-z0-9]+(?:[-\/][a-z0-9]+)*\z/', $prefix) !== 1) {
            throw new InvalidArgumentException('Platno requires an explicit, non-root route prefix.');
        }

        $router = app(Router::class);
        $namespace = trim($router->getLastGroupPrefix().'/'.$prefix, '/');
        $previous = $router->getRoutes()->getRoutes();

        foreach ($previous as $route) {
            if (self::occupies($route->uri(), $namespace) || str_starts_with($route->getName() ?? '', $namePrefix)) {
                throw new LogicException("Platno route namespace [{$namespace}] is already occupied.");
            }
        }

        $router->prefix($prefix)->group($register);
        $registered = array_values(array_filter($router->getRoutes()->getRoutes(), fn ($route) => ! in_array($route, $previous, true)));

        // Check later registrations too, including exact duplicates hidden by RouteCollection.
        app()->booted(function () use ($router, $registered, $namespace): void {
            $routes = $router->getRoutes()->getRoutes();
            $names = array_map(fn ($route) => $route->getName(), $registered);

            foreach ($registered as $route) {
                if (! in_array($route, $routes, true)) {
                    throw new LogicException("A route replaced Platno's reserved namespace [{$namespace}].");
                }
            }

            foreach ($routes as $route) {
                if ((self::occupies($route->uri(), $namespace) || in_array($route->getName(), $names, true)) && ! in_array($route, $registered, true)) {
                    throw new LogicException("A route conflicts with Platno's reserved namespace [{$namespace}].");
                }
            }
        });
    }

    private static function occupies(string $routePath, string $namespace): bool
    {
        return $routePath === $namespace || str_starts_with($routePath, $namespace.'/');
    }
}
