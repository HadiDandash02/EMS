<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route-level guard: ->middleware('role:manager,hr')
 * Coarse "which area may you enter"; fine-grained per-task rules live in TaskPolicy.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        $allowed = collect($roles)->map(fn (string $r) => UserRole::tryFrom($r));

        // The super admin may enter every area (read access; the policy still blocks approving).
        abort_unless(
            $user && ($user->isSuperAdmin() || $allowed->contains($user->role)),
            403,
            __('You do not have access to this area.')
        );

        return $next($request);
    }
}
