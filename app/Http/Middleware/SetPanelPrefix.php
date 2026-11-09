<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every signed-in page lives under the user's role: /admin/..., /hr/..., /manager/..., /employee/...
 *
 * - route('tasks.index') etc. automatically use the signed-in user's own prefix (URL defaults).
 *   This runs for EVERY web request, including Livewire's own /livewire/update calls,
 *   so links rendered during a component update also get the right prefix.
 * - Opening another role's prefix (e.g. an employee opening /admin/...) is a 404, so nobody
 *   can even see that another role's area exists.
 */
class SetPanelPrefix
{
    public function handle(Request $request, Closure $next): Response
    {
        $prefix = $request->user()?->url_prefix;

        if ($prefix) {
            URL::defaults(['panel' => $prefix]);
        }

        $route = $request->route();

        // Guests are left alone: the `auth` middleware sends them to the login page.
        if ($prefix && $route && $route->hasParameter('panel')) {
            // a user with several roles may open the prefix of ANY of their roles; everything else is a 404
            abort_unless(in_array($route->parameter('panel'), $request->user()->panelPrefixes(), true), 404);

            // Controllers / components do not need it; remove it so it is not passed to them.
            $route->forgetParameter('panel');
        }

        return $next($request);
    }
}
