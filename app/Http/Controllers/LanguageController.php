<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Switch the interface language (English / Arabic); remembered in the session. */
class LanguageController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(array_key_exists($locale, config('ems.locales')), 404);

        $request->session()->put('locale', $locale);

        return redirect()->back(fallback: '/');
    }
}
