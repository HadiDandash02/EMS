<?php

namespace App\Livewire\Admin;

use App\Models\Department;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Create / edit a department on its own page (/admin/departments/create, /admin/departments/{id}/edit). */
class DepartmentForm extends Component
{
    #[Locked]
    public ?int $departmentId = null;

    public string $name = '';

    public string $code = '';

    public function mount(?Department $department = null): void
    {
        Gate::authorize('manage-users');

        if ($department?->exists) {
            $this->departmentId = $department->id;
            $this->name = $department->name;
            $this->code = $department->code;
        }
    }

    public function save()
    {
        Gate::authorize('manage-users');
        $this->code = strtoupper(trim($this->code));

        $data = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:100', Rule::unique('departments', 'name')->ignore($this->departmentId)],
            'code' => ['required', 'regex:/^[A-Z0-9]{2,10}$/', Rule::unique('departments', 'code')->ignore($this->departmentId)],
        ], ['code.regex' => __('Use 2-10 letters or numbers (no spaces).')]);

        Department::updateOrCreate(['id' => $this->departmentId], $data);

        session()->flash('success', $this->departmentId ? __('Department updated.') : __('Department created.'));

        return $this->redirect(route('admin.departments'), navigate: true);
    }

    public function render()
    {
        Gate::authorize('manage-users');

        $department = $this->departmentId ? Department::withCount('employees')->with(['employees' => fn ($q) => $q->with('user')->limit(8)])->find($this->departmentId) : null;

        return view('livewire.admin.department-form', ['department' => $department])
            ->layout('components.layouts.app', ['title' => $this->departmentId ? 'Edit department' : 'New department']);
    }
}
