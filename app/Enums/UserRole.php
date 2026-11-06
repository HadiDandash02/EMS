<?php

namespace App\Enums;

enum UserRole: string
{
    case Employee = 'employee';
    case Manager = 'manager';
    case Hr = 'hr';

    public function label(): string
    {
        return match ($this) {
            self::Employee => 'Employee',
            self::Manager => 'Manager',
            self::Hr => 'HR',
        };
    }
}
