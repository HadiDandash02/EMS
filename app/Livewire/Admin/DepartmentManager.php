<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\HasFilterBar;
use App\Models\Department;
use App\Support\Sql;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class DepartmentManager extends Component
{
    use HasFilterBar, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public bool $formOpen = false;

    #[Locked]
    public ?int $departmentId = null;

    public string $name = '';

    public string $code = '';

    #[Locked]
    public ?int $deleteId = null;

    #[Locked]
    public string $deleteName = '';

    public function mount(): void
    {
        $this->guard();
    }

    protected function filterProperties(): array
    {
        return ['search'];
    }

    private function guard(): void
    {
        Gate::authorize('manage-users');
    }

    public function create(): void
    {
        $this->guard();
        $this->resetForm();
        $this->formOpen = true;
    }

    public function edit(int $id): void
    {
        $this->guard();
        $department = Department::findOrFail($id);

        $this->resetForm();
        $this->departmentId = $department->id;
        $this->name = $department->name;
        $this->code = $department->code;
        $this->formOpen = true;
    }

    public function save(): void
    {
        $this->guard();
        $this->code = strtoupper(trim($this->code));

        $data = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:100', Rule::unique('departments', 'name')->ignore($this->departmentId)],
            'code' => ['required', 'regex:/^[A-Z0-9]{2,10}$/', Rule::unique('departments', 'code')->ignore($this->departmentId)],
        ], ['code.regex' => 'Use 2-10 letters or numbers (no spaces).']);

        Department::updateOrCreate(['id' => $this->departmentId], $data);

        $this->dispatch('notify', type: 'success', message: $this->departmentId ? 'Department updated.' : 'Department created.');
        $this->formOpen = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->guard();
        $department = Department::findOrFail($id);

        $this->deleteId = $department->id;
        $this->deleteName = $department->name;
    }

    public function cancelDelete(): void
    {
        $this->deleteId = null;
    }

    public function delete(): void
    {
        $this->guard();
        $department = Department::withCount('employees')->findOrFail($this->deleteId);
        $this->deleteId = null;

        if ($department->employees_count > 0) {
            $this->dispatch('notify', type: 'error', message: "{$department->name} still has {$department->employees_count} employee(s). Move them first.");

            return;
        }

        $department->delete();
        $this->dispatch('notify', type: 'success', message: "{$department->name} was deleted.");
    }

    public function close(): void
    {
        $this->formOpen = false;
    }

    private function resetForm(): void
    {
        $this->resetValidation();
        $this->reset('departmentId', 'name', 'code');
    }

    public function render()
    {
        $this->guard();
        $term = trim($this->search);

        $departments = Department::query()
            ->withCount('employees')
            ->when($term !== '', fn ($q) => $q->where(function ($q) use ($term) {
                Sql::whereContains($q, 'departments.name', $term);
                Sql::whereContains($q, 'departments.code', $term, 'or');
            }))
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.admin.department-manager', ['departments' => $departments])
            ->layout('components.layouts.app', ['title' => 'Departments']);
    }
}
