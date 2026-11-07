<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** "/" and "/{role}" -> the signed-in user's own dashboard. */
class HomeController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        return redirect($request->user()->homeUrl());
    }
}
