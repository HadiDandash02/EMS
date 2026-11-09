<?php

namespace App\Livewire\Tasks;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Livewire\Concerns\HandlesTaskActions;
use App\Livewire\Concerns\HasFilterBar;
use App\Livewire\Concerns\SortsAndExports;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * One list component, three audiences (decided by the route, then locked):
 *   mine -> the user's own tasks
 *   team -> tasks of the manager's direct reports
 *   all  -> every task (HR)
 */
class TaskList extends Component
{
    use HandlesTaskActions, HasFilterBar, SortsAndExports, WithPagination;

    /** #[Locked] = the browser cannot tamper with it (e.g. switch "mine" to "all"). */
    #[Locked]
    public string $scope = 'mine';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** Picked from the searchable "Task" filter (a task id the user is allowed to see). */
    #[Url(as: 'task')]
    public $taskId = [];          // list of ids (multi-select)

    #[Url]
    public $status = [];          // list of status values

    #[Url]
    public $priority = [];        // list of priority values

    #[Url]
    public $employeeId = [];      // list of employee ids

    #[Url]
    public $departmentId = [];    // list of department ids

    #[Url(except: '')]
    public string $dateFrom = '';

    #[Url(except: '')]
    public string $dateTo = '';

    public function mount(string $scope = 'mine'): void
    {
        $user = auth()->user();

        abort_unless(match ($scope) {
            'mine' => $user->employee !== null,
            'team' => $user->isManager(),
            'all' => $user->canSeeAll(),
            default => false,
        }, 403);

        $this->scope = $scope;   // no default filter: every list starts with ALL statuses (a manager narrows it himself)
    }

    protected function filterProperties(): array
    {
        return ['search', 'taskId', 'status', 'priority', 'employeeId', 'departmentId', 'dateFrom', 'dateTo'];
    }

    #[On('task-updated')]
    public function refreshList(): void
    {
        // Intentionally empty: receiving the event triggers a re-render.
    }

    /** Every task this user may see in this list (own / team / all) - before any filter is applied. */
    private function scoped(): Builder
    {
        $user = auth()->user();

        return Task::query()
            ->when($this->scope === 'mine', fn (Builder $q) => $q->where('tasks.employee_id', $user->employee->id))
            ->when($this->scope === 'team', fn (Builder $q) => $q->forTeamOf($user))
            // Final safety net for every scope: never return a row the user could not open.
            ->visibleTo($user);
    }

    private function query(): Builder
    {
        $query = $this->scoped()
            ->with(['employee.user', 'employee.department', 'reviewer'])
            ->filter([
                'search' => trim($this->search),
                'task_id' => $this->ids($this->taskId),
                'status' => $this->enumValues($this->status, TaskStatus::class),
                'priority' => $this->enumValues($this->priority, TaskPriority::class),
                'employee_id' => $this->scope === 'mine' ? [] : $this->ids($this->employeeId),
                'department_id' => $this->scope === 'all' ? $this->ids($this->departmentId) : [],
                'date_from' => $this->validDate($this->dateFrom),
                'date_to' => $this->validDate($this->dateTo),
            ]);

        return $this->sortTasks($query);
    }

    // ---- sorting + export (SortsAndExports) ------------------------------------------------

    protected function sortableFields(): array
    {
        return ['title', 'employee', 'status', 'priority', 'due', 'created'];
    }

    private function sortTasks(Builder $query): Builder
    {
        [$field, $dir] = $this->activeSort();

        match ($field) {
            'title' => $query->orderBy('tasks.title', $dir),
            // sort by the employee's NAME (a correlated sub-query, no extra join in the main query)
            'employee' => $query->orderBy(fn ($q) => $q->select('users.name')->from('employees')
                ->join('users', 'users.id', '=', 'employees.user_id')->whereColumn('employees.id', 'tasks.employee_id')->limit(1), $dir),
            // enums sort by their logical order (Pending ... Cancelled, Low < Medium < High), not alphabetically
            'status' => $this->orderByList($query, 'tasks.status', TaskStatus::values(), $dir),
            'priority' => $this->orderByList($query, 'tasks.priority', array_map(fn ($p) => $p->value, TaskPriority::cases()), $dir),
            'due' => $query->orderByRaw('tasks.due_date IS NULL')->orderBy('tasks.due_date', $dir),   // tasks without a due date always last
            'created' => $query->orderBy('tasks.created_at', $dir),
            default => $query->latest('tasks.created_at'),
        };

        return $query->orderByDesc('tasks.id');   // stable order for equal values
    }

    protected function exportQuery(): Builder
    {
        return $this->query();
    }

    protected function exportFilename(): string
    {
        return 'tasks';
    }

    protected function exportColumns(): array
    {
        return [
            '#' => fn ($t) => $t->id,
            __('Task') => fn ($t) => $t->title,
            __('Employee') => fn ($t) => $t->employee->user->name,
            __('Department') => fn ($t) => $t->employee->department->name,
            __('Status') => fn ($t) => $t->status->label(),
            __('Priority') => fn ($t) => $t->priority->label(),
            __('Due') => fn ($t) => $t->due_date?->format('d/m/Y'),
            __('Created') => fn ($t) => $t->created_at->format('d/m/Y H:i'),
            __('Reviewed by') => fn ($t) => $t->reviewer?->name,
        ];
    }

    private function validDate(string $value): ?string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }

    public function render()
    {
        $user = auth()->user();

        $employees = match ($this->scope) {
            'all' => Employee::with('user')->get()->sortBy('user.name'),
            'team' => Employee::with('user')->where('manager_id', $user->employee->id)->get()->sortBy('user.name'),
            default => collect(),
        };

        // Options of the searchable "Task" filter: every task visible in this list, newest first.
        $taskOptions = $this->scoped()->with('employee.user:id,name')->latest('tasks.id')->get(['tasks.id', 'tasks.title', 'tasks.employee_id'])
            ->mapWithKeys(fn (Task $t) => [$t->id => $this->scope === 'mine' ? $t->title : $t->title.' — '.$t->employee->user->name]);

        return view('livewire.tasks.task-list', [
            'taskOptions' => $taskOptions,
            'tasks' => $this->query()->paginate($this->rowsPerPage()),
            'employees' => $employees,
            'departments' => $this->scope === 'all' ? Department::orderBy('name')->get() : collect(),
            'statuses' => TaskStatus::cases(),
            'priorities' => TaskPriority::cases(),
        ])->layout('components.layouts.app', ['title' => $this->title()]);
    }

    private function title(): string
    {
        return match ($this->scope) {
            'team' => 'Team tasks',
            'all' => 'All tasks',
            default => 'My tasks',
        };
    }
}
