<?php

namespace App\Livewire\Tasks;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Livewire\Concerns\HandlesTaskActions;
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
    use HandlesTaskActions, WithPagination;

    /** #[Locked] = the browser cannot tamper with it (e.g. switch "mine" to "all"). */
    #[Locked]
    public string $scope = 'mine';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $priority = '';

    #[Url(except: '')]
    public string $employeeId = '';

    #[Url(except: '')]
    public string $departmentId = '';

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
            'all' => $user->isHr(),
            default => false,
        }, 403);

        $this->scope = $scope;

        // Managers land on the approval queue by default.
        if ($scope === 'team' && $this->status === '' && ! request()->has('status')) {
            $this->status = TaskStatus::Completed->value;
        }
    }

    public function updated(string $property): void
    {
        // Any filter change must go back to page 1, otherwise we could land on an empty page.
        if ($property !== 'paginators') {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'status', 'priority', 'employeeId', 'departmentId', 'dateFrom', 'dateTo');
        $this->resetPage();
    }

    #[On('task-saved')]
    #[On('task-updated')]
    public function refreshList(): void
    {
        // Intentionally empty: receiving the event triggers a re-render.
    }

    private function query(): Builder
    {
        $user = auth()->user();

        $query = Task::query()
            ->with(['employee.user', 'employee.department', 'reviewer'])
            ->when($this->scope === 'mine', fn (Builder $q) => $q->where('tasks.employee_id', $user->employee->id))
            ->when($this->scope === 'team', fn (Builder $q) => $q->forTeamOf($user))
            // Final safety net for every scope: never return a row the user could not open.
            ->visibleTo($user)
            ->filter([
                'search' => trim($this->search),
                'status' => TaskStatus::tryFrom($this->status)?->value,
                'priority' => TaskPriority::tryFrom($this->priority)?->value,
                'employee_id' => $this->scope === 'mine' ? null : ((int) $this->employeeId ?: null),
                'department_id' => $this->scope === 'all' ? ((int) $this->departmentId ?: null) : null,
                'date_from' => $this->validDate($this->dateFrom),
                'date_to' => $this->validDate($this->dateTo),
            ]);

        return $query->latest('tasks.created_at')->latest('tasks.id');
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

        return view('livewire.tasks.task-list', [
            'tasks' => $this->query()->paginate(10),
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
