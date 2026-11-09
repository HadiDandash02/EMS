<?php

namespace App\Support;

use App\Enums\TaskAction;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Task;
use App\Models\TaskHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Numbers behind the dashboard charts. Every method takes a task query that is ALREADY scoped to what the
 * viewer may see (own tasks / the manager's team / the whole company), so a chart can never show more than
 * the user is allowed to read. Each statistic is one grouped query (no query per row).
 */
class DashboardStats
{
    /** @return array{labels: list<string>, values: list<int>, colors: list<string>, total: int} */
    public function byStatus(Builder $tasks): array
    {
        $counts = (clone $tasks)->reorder()->selectRaw('tasks.status as k, count(*) as c')->groupBy('tasks.status')->pluck('c', 'k');

        $labels = $values = $colors = [];
        foreach (TaskStatus::cases() as $s) {
            $labels[] = $s->label();
            $values[] = (int) ($counts[$s->value] ?? 0);
            $colors[] = self::STATUS_COLORS[$s->value];
        }

        return ['labels' => $labels, 'values' => $values, 'colors' => $colors, 'total' => array_sum($values)];
    }

    /** @return array{labels: list<string>, values: list<int>, colors: list<string>, total: int} */
    public function byPriority(Builder $tasks): array
    {
        $counts = (clone $tasks)->reorder()->selectRaw('tasks.priority as k, count(*) as c')->groupBy('tasks.priority')->pluck('c', 'k');

        $labels = $values = $colors = [];
        foreach (TaskPriority::cases() as $p) {
            $labels[] = $p->label();
            $values[] = (int) ($counts[$p->value] ?? 0);
            $colors[] = self::PRIORITY_COLORS[$p->value];
        }

        return ['labels' => $labels, 'values' => $values, 'colors' => $colors, 'total' => array_sum($values)];
    }

    /**
     * Tasks created per day for the last $days days (days without tasks count as 0, so the line is continuous).
     *
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    public function createdPerDay(Builder $tasks, int $days = 30): array
    {
        $from = Carbon::today()->subDays($days - 1);

        $counts = (clone $tasks)->reorder()->where('tasks.created_at', '>=', $from)
            ->selectRaw('date(tasks.created_at) as d, count(*) as c')->groupBy('d')->pluck('c', 'd');

        $labels = $values = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $from->copy()->addDays($i);
            $labels[] = $day->format('d/m');
            $values[] = (int) ($counts[$day->toDateString()] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values, 'total' => array_sum($values)];
    }

    /**
     * Headline numbers: total, open (not finished), overdue, due within a week and completion rate.
     *
     * @return array{total: int, open: int, overdue: int, due_soon: int, awaiting: int, done: int, completion: int}
     */
    public function headline(Builder $tasks): array
    {
        $open = [TaskStatus::Pending->value, TaskStatus::InProgress->value, TaskStatus::Rejected->value];
        $today = Carbon::today()->toDateString();
        $week = Carbon::today()->addDays(7)->toDateString();

        $row = (clone $tasks)->reorder()->selectRaw(
            'count(*) as total,
             sum(case when tasks.status in (?, ?, ?) then 1 else 0 end) as open_count,
             sum(case when tasks.status in (?, ?, ?) and tasks.due_date is not null and tasks.due_date < ? then 1 else 0 end) as overdue,
             sum(case when tasks.status in (?, ?, ?) and tasks.due_date is not null and tasks.due_date >= ? and tasks.due_date <= ? then 1 else 0 end) as due_soon,
             sum(case when tasks.status = ? then 1 else 0 end) as awaiting,
             sum(case when tasks.status in (?, ?) then 1 else 0 end) as done',
            [...$open, ...$open, $today, ...$open, $today, $week, TaskStatus::Completed->value, TaskStatus::Completed->value, TaskStatus::Approved->value]
        )->first();

        $total = (int) $row->total;
        $done = (int) $row->done;

        return [
            'total' => $total,
            'open' => (int) $row->open_count,
            'overdue' => (int) $row->overdue,
            'due_soon' => (int) $row->due_soon,
            'awaiting' => (int) $row->awaiting,
            'done' => $done,
            'completion' => $total > 0 ? (int) round($done / $total * 100) : 0,
        ];
    }

    /**
     * Tasks per employee, busiest first.
     *
     * @return array{labels: list<string>, values: list<int>}
     */
    public function perEmployee(Builder $tasks, int $limit = 8): array
    {
        $rows = (clone $tasks)->reorder()
            ->join('employees', 'employees.id', '=', 'tasks.employee_id')->join('users', 'users.id', '=', 'employees.user_id')
            ->selectRaw('users.name as k, count(*) as c')->groupBy('employees.id', 'users.name')->orderByDesc('c')->limit($limit)->pluck('c', 'k');

        return ['labels' => $rows->keys()->all(), 'values' => $rows->map(fn ($v) => (int) $v)->values()->all()];
    }

    /** @return array{labels: list<string>, values: list<int>} */
    public function perDepartment(Builder $tasks): array
    {
        $rows = (clone $tasks)->reorder()
            ->join('employees', 'employees.id', '=', 'tasks.employee_id')->join('departments', 'departments.id', '=', 'employees.department_id')
            ->selectRaw('departments.name as k, count(*) as c')->groupBy('departments.id', 'departments.name')->orderByDesc('c')->pluck('c', 'k');

        return ['labels' => $rows->keys()->all(), 'values' => $rows->map(fn ($v) => (int) $v)->values()->all()];
    }

    /**
     * Approved vs rejected decisions (optionally only those taken by one user), counting only tasks that still exist.
     *
     * @return array{labels: list<string>, values: list<int>, colors: list<string>, total: int}
     */
    public function decisions(?User $by = null): array
    {
        $counts = TaskHistory::query()->whereIn('action', [TaskAction::Approved, TaskAction::Rejected])->whereHas('task')
            ->when($by, fn ($q) => $q->where('actor_id', $by->id))
            ->selectRaw('action as k, count(*) as c')->groupBy('action')->pluck('c', 'k');

        $values = [(int) ($counts[TaskAction::Approved->value] ?? 0), (int) ($counts[TaskAction::Rejected->value] ?? 0)];

        return ['labels' => [__('Approved'), __('Rejected')], 'values' => $values, 'colors' => [self::STATUS_COLORS['APPROVED'], self::STATUS_COLORS['REJECTED']], 'total' => array_sum($values)];
    }

    /** @return array{labels: list<string>, values: list<int>, total: int} */
    public function usersByRole(): array
    {
        $counts = User::query()->selectRaw('role as k, count(*) as c')->groupBy('role')->pluck('c', 'k');

        $labels = $values = [];
        foreach (\App\Enums\UserRole::cases() as $r) {
            $labels[] = $r->label();
            $values[] = (int) ($counts[$r->value] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values, 'total' => array_sum($values)];
    }

    /** @return array{employees: int, departments: int, users: int} */
    public function company(): array
    {
        return ['employees' => Employee::count(), 'departments' => Department::count(), 'users' => DB::table('users')->count()];
    }

    public const STATUS_COLORS = [
        'PENDING' => '#94a3b8', 'IN_PROGRESS' => '#3b82f6', 'COMPLETED' => '#f59e0b',
        'APPROVED' => '#10b981', 'REJECTED' => '#f43f5e', 'CANCELLED' => '#a1a1aa',
    ];

    public const PRIORITY_COLORS = ['low' => '#94a3b8', 'medium' => '#f59e0b', 'high' => '#f43f5e'];
}
