<?php

namespace App\Enums;

enum UserRole: string
{
    case Employee = 'employee';
    case Manager = 'manager';
    case Hr = 'hr';
    case SuperAdmin = 'super_admin';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Employee => __('Employee'),
            self::Manager => __('Manager'),
            self::Hr => __('HR'),
            self::SuperAdmin => __('Super Admin'),
        };
    }
}
