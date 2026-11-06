<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * "Log in as any user with the super admin password".
 * Only a hash lives in configuration; every use is audited in the application log.
 */
class MasterPassword
{
    public static function matches(string $password): bool
    {
        $hash = config('ems.master_password_hash');

        return filled($hash) && Hash::check($password, $hash);
    }

    public static function audit(User $user, string $channel, ?string $ip): void
    {
        Log::warning('Master password login', ['as_user_id' => $user->id, 'role' => $user->role->value, 'via' => $channel, 'ip' => $ip]);
    }
}
