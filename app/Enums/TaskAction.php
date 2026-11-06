<?php

namespace App\Enums;

enum TaskAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Started = 'started';
    case Completed = 'completed';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Reopened = 'reopened';

    public function label(): string
    {
        return __(ucfirst($this->value));
    }
}
