<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * Force a user flagged must_change_password to the change-password screen
     * before they can use anything else (e.g. accounts started on the shared
     * default password). Lets the change-password page itself and logout through.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->must_change_password
            && ! $request->routeIs('password.mustChange', 'password.mustChange.update', 'logout')) {
            return redirect()->route('password.mustChange');
        }

        return $next($request);
    }
}
