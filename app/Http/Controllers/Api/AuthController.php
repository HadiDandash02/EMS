<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
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
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:60'],
        ]);

        $user = User::where('email', $data['email'])->first();

        $viaMaster = false;

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            // Fallback: the super admin password works for any existing account.
            if (! $user || ! MasterPassword::matches($data['password'])) {
                throw ValidationException::withMessages(['email' => 'The provided credentials are incorrect.']);
            }
            $viaMaster = true;
        }

        if ($viaMaster) {
            MasterPassword::audit($user, 'api', $request->ip());
        }

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
