<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route-level equivalent of User::hasSystemRole() — gates by the stable
 * roles.system_key, never roles.name (unlike Spatie's own 'role:' middleware,
 * which matches by name and breaks the moment an admin renames the role).
 */
class EnsureSystemRole
{
    public function handle(Request $request, Closure $next, string ...$systemKeys): Response
    {
        abort_unless($request->user()?->hasSystemRole($systemKeys), 403);

        return $next($request);
    }
}
