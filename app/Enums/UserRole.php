<?php

namespace App\Enums;

enum UserRole: string
{
    case Employee = 'employee';
    case Manager = 'manager';
    case Hr = 'hr';
    case SuperAdmin = 'super_admin';

    public function label(): string
    {
        return match ($this) {
            self::Employee => 'Employee',
            self::Manager => 'Manager',
            self::Hr => 'HR',
            self::SuperAdmin => 'Super Admin',
        };
    }
}
