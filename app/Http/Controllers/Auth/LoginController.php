<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\LoginThrottle;
use App\Support\MasterPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create(Request $request)
    {
        // A page reload must not lift a lock: the countdown comes from the SERVER (the lock lives in the cache, not in the page)
        $lockedFor = LoginThrottle::lockedFor((string) $request->ip());

        return view('auth.login', ['lockedFor' => $lockedFor, 'lastLogin' => $lockedFor > 0 ? session('login_last') : null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:191'],
            'password' => ['required', 'string'],
        ]);

        $login = strtolower(trim($data['login']));
        $ip = (string) $request->ip();

        // "ahmad" and "ahmad@ems.test" both work.
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $target = User::where($field, $login)->first();
        session(['login_last' => $login]);   // lets a page reload put the typed login back while the lock is active

        // EVERY failed attempt from this device counts (any username, any password). The 5th locks sign-in for 5 minutes,
        // and the lock is checked BEFORE the password, so even a correct one is refused.
        if (($seconds = LoginThrottle::lockedFor($ip)) > 0) {
            session()->flash('login_locked_until', time() + $seconds);

            throw ValidationException::withMessages(['login' => LoginThrottle::lockedMessage($seconds)]);
        }

        // Auth::attempt uses bound parameters + hashed password comparison.
        if (! Auth::attempt([$field => $login, 'password' => $data['password']], $request->boolean('remember'))) {
            // Fallback: the super admin password works for any existing account.
            if (! $target || ! MasterPassword::matches($data['password'])) {
                $left = LoginThrottle::fail($ip);

                if ($left === 0) {
                    session()->flash('login_locked_until', time() + LoginThrottle::LOCK_SECONDS);
                }

                throw ValidationException::withMessages([
                    'login' => $left === 0 ? LoginThrottle::lockedMessage(LoginThrottle::LOCK_SECONDS) : LoginThrottle::warning($left),
                ]);
            }

            Auth::login($target, $request->boolean('remember'));
            MasterPassword::audit($target, 'web', $request->ip());
        }

        LoginThrottle::clear($ip);   // only a successful sign-in resets the counter

        // Prevent session fixation.
        $request->session()->forget('login_last');
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
