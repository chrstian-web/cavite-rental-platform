<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOwnerIsVerified
{
    /**
     * Gates owner-only features behind a verified owner_verification_status.
     * Managers and Super Admin are never subject to this pipeline — only
     * accounts with the 'owner' role are checked (User::isOwnerVerified()
     * returns true immediately for any other role).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isOwnerVerified()) {
            return redirect()->route('owner.verification.show')
                ->with('status', 'Please complete owner verification before accessing this page.');
        }

        return $next($request);
    }
}
