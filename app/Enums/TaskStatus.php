<?php

namespace App\Enums;

/**
 * Task lifecycle. The allowed transitions are defined in ONE place (transitions())
 * so the web UI, the API and the policies can never disagree.
 *
 *  PENDING -> IN_PROGRESS -> COMPLETED -> APPROVED   (terminal)
 *  PENDING -> COMPLETED               \-> REJECTED -> IN_PROGRESS (rework)
 *  PENDING -> CANCELLED (terminal)
 */
enum TaskStatus: string
{
    case Pending = 'PENDING';
    case InProgress = 'IN_PROGRESS';
    case Completed = 'COMPLETED';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';
    case Cancelled = 'CANCELLED';

    /** @return list<self> */
    public function transitions(): array
    {
        return match ($this) {
            self::Pending => [self::InProgress, self::Completed, self::Cancelled],
            self::InProgress => [self::Completed],
            self::Completed => [self::Approved, self::Rejected],
            self::Rejected => [self::InProgress],
            self::Approved, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->transitions(), true);
    }

    public function isFinal(): bool
    {
        return $this->transitions() === [];
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::InProgress => __('In progress'),
            self::Completed => __('Completed'),
            self::Approved => __('Approved'),
            self::Rejected => __('Rejected'),
            self::Cancelled => __('Cancelled'),
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-slate-100 text-slate-700 ring-slate-300',
            self::InProgress => 'bg-blue-50 text-blue-700 ring-blue-200',
            self::Completed => 'bg-amber-50 text-amber-700 ring-amber-200',
            self::Approved => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            self::Rejected => 'bg-rose-50 text-rose-700 ring-rose-200',
            self::Cancelled => 'bg-violet-50 text-violet-700 ring-violet-200',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
