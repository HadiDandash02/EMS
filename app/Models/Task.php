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
use Illuminate\Support\Arr;

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
        if ($user->isSuperAdmin()) {
            return $query;
        }

        $employeeId = $user->employee?->id;
        $department = $user->confinedDepartmentId();

        if ($user->isHr()) {
            // HR: every task of THEIR department - and nothing from any other department
            return $query->whereIn('tasks.employee_id', Employee::query()->select('id')->where('department_id', $department));
        }

        if ($user->role === UserRole::Manager) {
            // own tasks + tasks of direct reports who are in the manager's own department
            return $query->where(function (Builder $q) use ($employeeId, $department) {
                $q->where('tasks.employee_id', $employeeId)
                    ->orWhereIn('tasks.employee_id', Employee::query()
                        ->select('id')
                        ->where('manager_id', $employeeId)
                        ->where('department_id', $department));
            });
        }

        return $query->where('tasks.employee_id', $employeeId);
    }

    /** Tasks that belong to the employees directly managed by the given user. */
    public function scopeForTeamOf(Builder $query, User $manager): Builder
    {
        return $query->whereIn('tasks.employee_id', Employee::query()
            ->select('id')
            ->where('manager_id', $manager->employee?->id ?? 0)
            ->where('department_id', $manager->confinedDepartmentId() ?? 0));   // a team never crosses the department border
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
            // each of these accepts ONE value or a LIST (multi-select filters)
            ->when(Arr::wrap($filters['task_id'] ?? []), fn (Builder $q, array $v) => $q->whereIn('tasks.id', $v))
            ->when(Arr::wrap($filters['status'] ?? []), fn (Builder $q, array $v) => $q->whereIn('tasks.status', $v))
            ->when(Arr::wrap($filters['priority'] ?? []), fn (Builder $q, array $v) => $q->whereIn('tasks.priority', $v))
            ->when(Arr::wrap($filters['employee_id'] ?? []), fn (Builder $q, array $v) => $q->whereIn('tasks.employee_id', $v))
            ->when(Arr::wrap($filters['department_id'] ?? []), function (Builder $q, array $v) {
                $q->whereIn('tasks.employee_id', Employee::query()->select('id')->whereIn('department_id', $v));
            })
            ->when($filters['date_from'] ?? null, fn (Builder $q, string $v) => $q->whereDate('tasks.created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn (Builder $q, string $v) => $q->whereDate('tasks.created_at', '<=', $v))
            // [CHANGE-REQUEST #2: new filter "due before"] (1/5) UNCOMMENT the next line and move the ';' from the line above to its end
            // ->when($filters['due_before'] ?? null, fn (Builder $q, string $v) => $q->whereDate('tasks.due_date', '<=', $v))
            ;
    }

    /** Where the breadcrumb "Tasks" leads for this user: own list, the manager's team list, or the company list. */
    public function listUrlFor(User $user): string
    {
        return match (true) {
            $this->isOwnedBy($user) => route('tasks.index'),
            $user->canSeeAll() => route('tasks.all'),
            default => route('team.tasks'),
        };
    }

    public function isOwnedBy(User $user): bool
    {
        return $user->employee !== null && $this->employee_id === $user->employee->id;
    }
}
