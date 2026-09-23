<?php

declare(strict_types=1);

namespace Platno\Tests\Feature;

use Illuminate\Support\Facades\Route;
use LogicException;
use Platno\Platno;
use Platno\Tests\TestCase;

final class RoutingTest extends TestCase
{
    // Prevent installation exposing an editor or taking over public host routes.
    public function test_provider_does_not_mount_any_routes_without_explicit_integration(): void
    {
        $this->get('/platno')->assertNotFound();
        $this->get('/pages/example')->assertNotFound();
        self::assertFalse(Route::has('platno.public.show'));
    }

    // Prevent route registration silently replacing an existing host endpoint.
    public function test_public_routes_refuse_an_occupied_namespace_without_replacing_it(): void
    {
        Route::get('/pages/{slug}', fn () => response('Host content'));

        try {
            Platno::publicRoutes();
            self::fail('A namespace collision must be rejected.');
        } catch (LogicException) {
            $this->get('/pages/retained')->assertOk()->assertSee('Host content');
        }
    }

    // Prevent editor form targets being redirected to a conflicting host route name.
    public function test_editor_refuses_conflicting_names_outside_its_path(): void
    {
        Route::post('/host-pages', fn () => response('Host pages'))->name('platno.pages.store');

        $this->expectException(LogicException::class);
        Platno::editorRoutes();
    }
}
