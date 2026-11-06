<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\HasFilterBar;
use App\Models\Department;
use App\Support\Sql;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Super admin: the list of departments. Create / edit / delete open their own pages. */
class DepartmentManager extends Component
{
    use HasFilterBar, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('manage-users');
    }

    protected function filterProperties(): array
    {
        return ['search'];
    }

    public function render()
    {
        Gate::authorize('manage-users');
        $term = trim($this->search);

        $departments = Department::query()
            ->withCount('employees')
            ->when($term !== '', fn ($q) => $q->where(function ($q) use ($term) {
                Sql::whereContains($q, 'departments.name', $term);
                Sql::whereContains($q, 'departments.code', $term, 'or');
            }))
            ->orderBy('name')
            ->paginate($this->rowsPerPage());

        return view('livewire.admin.department-manager', ['departments' => $departments])
            ->layout('components.layouts.app', ['title' => 'Departments']);
    }
}
