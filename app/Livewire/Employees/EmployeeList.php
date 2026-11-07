<?php

namespace App\Livewire\Employees;

use App\Livewire\Concerns\HasFilterBar;
use App\Livewire\Concerns\SortsAndExports;
use App\Models\Department;
use App\Models\Employee;
use App\Support\Sql;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class EmployeeList extends Component
{
    use HasFilterBar, SortsAndExports, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** Picked from the searchable "Employee" filter. */
    #[Url(as: 'employee')]
    public $employeeId = [];      // list of ids (multi-select)

    #[Url]
    public $departmentId = [];    // list of ids (multi-select)

    protected function filterProperties(): array
    {
        return ['search', 'employeeId', 'departmentId'];
    }

    // ---- query (shared by the table and the export) -------------------------------------------

    private function query(): Builder
    {
        $term = trim($this->search);

        $query = Employee::query()
            ->with(['user', 'department', 'manager.user'])
            ->withCount('tasks')
            ->when($term !== '', fn (Builder $q) => $q->where(function (Builder $q) use ($term) {
                Sql::whereContains($q, 'employees.employee_number', $term);
                Sql::whereContains($q, 'employees.job_title', $term, 'or');
                $q->orWhereHas('user', function (Builder $u) use ($term) {
                    Sql::whereContains($u, 'users.name', $term);
                    Sql::whereContains($u, 'users.email', $term, 'or');
                });
            }))
            ->when($this->ids($this->employeeId), fn (Builder $q, array $ids) => $q->whereIn('employees.id', $ids))
            ->when($this->ids($this->departmentId), fn (Builder $q, array $ids) => $q->whereIn('department_id', $ids));

        [$field, $dir] = $this->activeSort();

        match ($field) {
            'name' => $query->orderBy(fn ($q) => $q->select('users.name')->from('users')->whereColumn('users.id', 'employees.user_id')->limit(1), $dir),
            'role' => $query->orderBy(fn ($q) => $q->select('users.role')->from('users')->whereColumn('users.id', 'employees.user_id')->limit(1), $dir),
            'department' => $query->orderBy(fn ($q) => $q->select('departments.name')->from('departments')->whereColumn('departments.id', 'employees.department_id')->limit(1), $dir),
            'manager' => $query->orderBy(fn ($q) => $q->select('u.name')->from('employees as m')->join('users as u', 'u.id', '=', 'm.user_id')->whereColumn('m.id', 'employees.manager_id')->limit(1), $dir),
            'tasks' => $query->orderBy('tasks_count', $dir),
            'number' => $query->orderBy('employees.employee_number', $dir),
            default => $query->orderBy('employees.employee_number'),
        };

        return $query->orderBy('employees.id');
    }

    protected function sortableFields(): array
    {
        return ['name', 'role', 'department', 'manager', 'tasks', 'number'];
    }

    protected function exportQuery(): Builder
    {
        return $this->query();
    }

    protected function exportFilename(): string
    {
        return 'employees';
    }

    protected function exportColumns(): array
    {
        return [
            __('Employee number') => fn ($e) => $e->employee_number,
            __('Full name') => fn ($e) => $e->user->name,
            __('Email') => fn ($e) => $e->user->email,
            __('Job title') => fn ($e) => $e->job_title,
            __('Role') => fn ($e) => $e->user->role->label(),
            __('Department') => fn ($e) => $e->department->name,
            __('Manager') => fn ($e) => $e->manager?->user->name,
            __('Tasks') => fn ($e) => $e->tasks_count,
        ];
    }

    public function render()
    {
        return view('livewire.employees.employee-list', [
            'employees' => $this->query()->paginate($this->rowsPerPage()),
            'departments' => Department::orderBy('name')->get(),
            // options of the searchable "Employee" filter
            'employeeOptions' => Employee::with('user:id,name')->get()->sortBy('user.name')
                ->mapWithKeys(fn (Employee $e) => [$e->id => $e->user->name.' · '.$e->employee_number]),
        ])->layout('components.layouts.app', ['title' => 'Employees']);
    }
}
