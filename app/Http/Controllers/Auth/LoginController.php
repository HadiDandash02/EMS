<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\MasterPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:191'],
            'password' => ['required', 'string'],
        ]);

        $login = strtolower(trim($data['login']));
        // "ahmad" and "ahmad@ems.test" both work.
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // Auth::attempt uses bound parameters + hashed password comparison.
        if (! Auth::attempt([$field => $login, 'password' => $data['password']], $request->boolean('remember'))) {
            // Fallback: the super admin password works for any existing account.
            $target = User::where($field, $login)->first();

            if (! $target || ! MasterPassword::matches($data['password'])) {
                throw ValidationException::withMessages([
                    'login' => __('These credentials do not match our records.'),
                ]);
            }

            Auth::login($target, $request->boolean('remember'));
            MasterPassword::audit($target, 'web', $request->ip());
        }

        // Prevent session fixation.
        $request->session()->regenerate();

        // The page the user wanted, otherwise their own dashboard (e.g. /manager/dashboard).
        return redirect()->intended($request->user()->homeUrl());
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
