<?php

namespace App\Livewire\Employees;

use App\Livewire\Concerns\HasFilterBar;
use App\Models\Department;
use App\Models\Employee;
use App\Support\Sql;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class EmployeeList extends Component
{
    use HasFilterBar, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $departmentId = '';

    protected function filterProperties(): array
    {
        return ['search', 'departmentId'];
    }

    public function render()
    {
        $term = trim($this->search);

        $employees = Employee::query()
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
            ->when((int) $this->departmentId, fn (Builder $q, int $id) => $q->where('department_id', $id))
            ->orderBy('employee_number')
            ->paginate(10);

        return view('livewire.employees.employee-list', [
            'employees' => $employees,
            'departments' => Department::orderBy('name')->get(),
        ])->layout('components.layouts.app', ['title' => 'Employees']);
    }
}
