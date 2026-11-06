<?php

namespace App\Models;

use App\Enums\TaskAction;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'actor_id',
        'action',
        'from_status',
        'to_status',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'action' => TaskAction::class,
            'from_status' => TaskStatus::class,
            'to_status' => TaskStatus::class,
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
