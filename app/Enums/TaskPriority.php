<?php

namespace App\Enums;

enum TaskPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return __(ucfirst($this->value));
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Low => 'bg-slate-100 text-slate-700 ring-slate-200',
            self::Medium => 'bg-amber-50 text-amber-700 ring-amber-200',
            self::High => 'bg-rose-50 text-rose-700 ring-rose-200',
        };
    }
}
