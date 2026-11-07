<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Support\Sql;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    /**
     * Only fields an end user may legitimately submit. `status`, `employee_id`,
     * `reviewed_*` and `rejection_reason` are NEVER mass assigned from a request:
     * they are changed only through TaskWorkflowService.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'priority',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'due_date' => 'date',
            'reviewed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TaskHistory::class)->latest('id');
    }

    /**
     * Single source of truth for "which tasks may this user see?".
     * Used by the Livewire lists AND the API, so a list can never leak rows
     * that the policy would refuse individually.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->canSeeAll()) {
            return $query;
        }

        $employeeId = $user->employee?->id;

        if ($user->role === UserRole::Manager) {
            // own tasks + tasks of direct reports
            return $query->where(function (Builder $q) use ($employeeId) {
                $q->where('tasks.employee_id', $employeeId)
                    ->orWhereIn('tasks.employee_id', Employee::query()
                        ->select('id')
                        ->where('manager_id', $employeeId));
            });
        }

        return $query->where('tasks.employee_id', $employeeId);
    }

    /** Tasks that belong to the employees directly managed by the given user. */
    public function scopeForTeamOf(Builder $query, User $manager): Builder
    {
        return $query->whereIn('tasks.employee_id', Employee::query()
            ->select('id')
            ->where('manager_id', $manager->employee?->id ?? 0));
    }

    /**
     * Search / filter used by every list screen and the API.
     * All values are bound parameters (no SQL injection) and unknown keys are ignored.
     *
     * @param  array{search?:?string,status?:?string,priority?:?string,employee_id?:int|string|null,department_id?:int|string|null,date_from?:?string,date_to?:?string}  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, fn (Builder $q, string $term) => Sql::whereContains($q, 'tasks.title', $term))
            ->when($filters['task_id'] ?? null, fn (Builder $q, $v) => $q->where('tasks.id', $v))
            ->when($filters['status'] ?? null, fn (Builder $q, string $v) => $q->where('tasks.status', $v))
            ->when($filters['priority'] ?? null, fn (Builder $q, string $v) => $q->where('tasks.priority', $v))
            ->when($filters['employee_id'] ?? null, fn (Builder $q, $v) => $q->where('tasks.employee_id', $v))
            ->when($filters['department_id'] ?? null, function (Builder $q, $v) {
                $q->whereIn('tasks.employee_id', Employee::query()->select('id')->where('department_id', $v));
            })
            ->when($filters['date_from'] ?? null, fn (Builder $q, string $v) => $q->whereDate('tasks.created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn (Builder $q, string $v) => $q->whereDate('tasks.created_at', '<=', $v));
    }

    public function isOwnedBy(User $user): bool
    {
        return $user->employee !== null && $this->employee_id === $user->employee->id;
    }
}
