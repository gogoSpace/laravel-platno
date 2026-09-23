<?php

declare(strict_types=1);

namespace Platno\Tests\Fixtures;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Test-owned middleware stands in for the consuming application's access decision.
final class HostEditorAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->header('X-Test-Host-Editor') === 'allowed', 403);

        return $next($request);
    }
}
