<?php

namespace App\Livewire\Employees;

use App\Models\Department;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class EmployeeList extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $departmentId = '';

    public function updated(string $property): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $term = addcslashes(trim($this->search), '%_\\');

        $employees = Employee::query()
            ->with(['user', 'department', 'manager.user'])
            ->withCount('tasks')
            ->when($term !== '', fn (Builder $q) => $q->where(function (Builder $q) use ($term) {
                $q->where('employee_number', 'like', "%{$term}%")
                    ->orWhere('job_title', 'like', "%{$term}%")
                    ->orWhereHas('user', fn (Builder $u) => $u->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"));
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
