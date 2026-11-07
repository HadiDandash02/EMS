<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\HasFilterBar;
use App\Livewire\Concerns\SortsAndExports;
use App\Models\Department;
use App\Support\Sql;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Super admin: the list of departments. Create / edit / delete open their own pages. */
class DepartmentManager extends Component
{
    use HasFilterBar, SortsAndExports, WithPagination;

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

    private function query(): Builder
    {
        Gate::authorize('manage-users');
        $term = trim($this->search);

        $query = Department::query()
            ->withCount('employees')
            ->when($term !== '', fn ($q) => $q->where(function ($q) use ($term) {
                Sql::whereContains($q, 'departments.name', $term);
                Sql::whereContains($q, 'departments.code', $term, 'or');
            }));

        [$field, $dir] = $this->activeSort();

        match ($field) {
            'name' => $query->orderBy('departments.name', $dir),
            'code' => $query->orderBy('departments.code', $dir),
            'employees' => $query->orderBy('employees_count', $dir),
            default => $query->orderBy('departments.name'),
        };

        return $query->orderBy('departments.id');
    }

    protected function sortableFields(): array
    {
        return ['name', 'code', 'employees'];
    }

    protected function exportQuery(): Builder
    {
        return $this->query();
    }

    protected function exportFilename(): string
    {
        return 'departments';
    }

    protected function exportColumns(): array
    {
        return [
            __('Department') => fn ($d) => $d->name,
            __('Code') => fn ($d) => $d->code,
            __('Employees') => fn ($d) => $d->employees_count,
        ];
    }

    public function render()
    {
        return view('livewire.admin.department-manager', ['departments' => $this->query()->paginate($this->rowsPerPage())])
            ->layout('components.layouts.app', ['title' => 'Departments']);
    }
}
