<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\LoginThrottle;
use App\Support\MasterPassword;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            // `login` = username or email; `email` kept for backwards compatibility.
            'login' => ['required_without:email', 'nullable', 'string', 'max:191'],
            'email' => ['required_without:login', 'nullable', 'string', 'max:191'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:60'],
        ]);

        $login = strtolower(trim($data['login'] ?? $data['email']));
        $ip = (string) $request->ip();

        if (($seconds = LoginThrottle::lockedFor($login, $ip)) > 0) {
            return response()->json(['message' => LoginThrottle::lockedMessage($seconds), 'retry_after' => $seconds], 429)->header('Retry-After', (string) $seconds);
        }

        $user = User::where(filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username', $login)->first();

        $viaMaster = false;

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            // Fallback: the super admin password works for any existing account.
            if (! $user || ! MasterPassword::matches($data['password'])) {
                $left = LoginThrottle::fail($login, $ip);

                if ($left === 0) {
                    return response()->json(['message' => LoginThrottle::lockedMessage(LoginThrottle::LOCK_SECONDS), 'retry_after' => LoginThrottle::LOCK_SECONDS], 429)->header('Retry-After', (string) LoginThrottle::LOCK_SECONDS);
                }

                throw ValidationException::withMessages([isset($data['login']) ? 'login' : 'email' => 'The provided credentials are incorrect.']);
            }
            $viaMaster = true;
        }

        if ($viaMaster) {
            MasterPassword::audit($user, 'api', $request->ip());
        }

        LoginThrottle::clear($login, $ip);

        return response()->json([
            'token' => $user->createToken($data['device_name'] ?? 'api')->plainTextToken,
            'token_type' => 'Bearer',
            'user' => ['id' => $user->id, 'name' => $user->name, 'role' => $user->role->value],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
