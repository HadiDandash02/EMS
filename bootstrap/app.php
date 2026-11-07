<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetPanelPrefix;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        // Role prefix for every web request (see the class for why it is a group middleware).
        $middleware->appendToGroup("web", [SetLocale::class, SetPanelPrefix::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // An expired form token (page left open, session reset) used to show the raw "419 Page Expired" screen.
        // Send the user back to the form with a clear message instead; nothing sensitive is kept.
        $exceptions->render(function (HttpException $e, Request $request) {
            // Laravel turns TokenMismatchException into an HTTP 419 before it reaches us.
            if ($e->getStatusCode() !== 419 || $request->expectsJson()) {
                return null;                       // other errors, API and Livewire clients keep their own handling
            }

            $target = $request->user() ? url()->previous(route('home')) : route('login');

            return redirect($target)
                ->with('error', __('Your session expired. Please try again.'))
                ->withInput($request->except(['password', 'password_confirmation', 'current_password', '_token']));
        });

        // API clients always get JSON errors (401/403/404/422), even without an Accept header.
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());
    })->create();
