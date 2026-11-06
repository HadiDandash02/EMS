<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

/**
 * The ONE definition of a strong password, used by the profile page, the admin user form and
 * (mirrored in the browser by <x-password-input meter>) so the checklist and the server always agree.
 *
 *   at least 10 characters · upper AND lower case · a number · a symbol
 */
class PasswordPolicy
{
    public const MIN_LENGTH = 10;

    public static function rule(): Password
    {
        return Password::min(self::MIN_LENGTH)->mixedCase()->numbers()->symbols();
    }
}
