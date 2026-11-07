<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Old addresses without the role prefix (bookmarks): /tasks/5 -> /employee/tasks/5 (301). */
class LegacyRedirectController extends Controller
{
    public function __invoke(Request $request, string $path): RedirectResponse
    {
        $query = $request->getQueryString();

        return redirect(url($request->user()->url_prefix.'/'.$path).($query ? '?'.$query : ''), 301);
    }
}
