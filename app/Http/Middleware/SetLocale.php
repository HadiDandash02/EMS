<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Language of the interface: English or Arabic (config/ems.php "locales").
 * Chosen with the switcher in the top bar and remembered in the session.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale', config('app.locale'));

        if (! array_key_exists($locale, config('ems.locales'))) {
            $locale = config('app.locale');
        }

        app()->setLocale($locale); // also switches Carbon, so dates/days are translated

        return $next($request);
    }
}
