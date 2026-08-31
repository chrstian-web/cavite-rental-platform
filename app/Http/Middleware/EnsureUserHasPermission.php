<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    /**
     * Usage in routes: ->middleware('permission:properties.verify')
     * Super admins always pass, since they hold every permission by seed data,
     * but this check is cheap so we don't special-case it.
     */
    public function handle(Request $request, Closure $next, string $permissionSlug): Response
    {
        $user = $request->user();

        if (! $user || ! $user->role) {
            abort(403);
        }

        $hasPermission = $user->role->permissions()
            ->where('slug', $permissionSlug)
            ->exists();

        if (! $hasPermission) {
            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
