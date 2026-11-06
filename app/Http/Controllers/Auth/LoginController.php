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
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Auth::attempt uses bound parameters + hashed password comparison.
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            // Fallback: the super admin password works for any existing account.
            $target = User::where('email', $credentials['email'])->first();

            if (! $target || ! MasterPassword::matches($credentials['password'])) {
                throw ValidationException::withMessages([
                    'email' => 'These credentials do not match our records.',
                ]);
            }

            Auth::login($target, $request->boolean('remember'));
            MasterPassword::audit($target, 'web', $request->ip());
        }

        // Prevent session fixation.
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
