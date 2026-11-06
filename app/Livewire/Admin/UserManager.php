<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Livewire\Concerns\HasFilterBar;
use App\Models\Department;
use App\Models\User;
use App\Support\Sql;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Super admin: the list of accounts (search, filters, pagination).
 * Create / edit / delete each open their own page (UserForm, DeleteUser).
 */
class UserManager extends Component
{
    use HasFilterBar, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $roleFilter = '';

    #[Url(except: '')]
    public string $departmentFilter = '';

    public function mount(): void
    {
        Gate::authorize('manage-users');
    }

    protected function filterProperties(): array
    {
        return ['search', 'roleFilter', 'departmentFilter'];
    }

    public function render()
    {
        Gate::authorize('manage-users');
        $term = trim($this->search);

        $users = User::query()
            ->with(['employee.department', 'employee.manager.user'])
            ->when($term !== '', fn (Builder $q) => $q->where(function (Builder $q) use ($term) {
                Sql::whereContains($q, 'users.name', $term);
                Sql::whereContains($q, 'users.username', $term, 'or');
                Sql::whereContains($q, 'users.email', $term, 'or');
                $q->orWhereHas('employee', fn (Builder $e) => Sql::whereContains($e, 'employees.employee_number', $term));
            }))
            ->when(UserRole::tryFrom($this->roleFilter), fn (Builder $q, UserRole $r) => $q->where('role', $r))
            ->when((int) $this->departmentFilter, fn (Builder $q, int $id) => $q->whereHas('employee', fn (Builder $e) => $e->where('department_id', $id)))
            ->orderBy('name')
            ->paginate($this->rowsPerPage());

        return view('livewire.admin.user-manager', [
            'users' => $users,
            'departments' => Department::orderBy('name')->get(),
            'roles' => UserRole::cases(),
        ])->layout('components.layouts.app', ['title' => 'User management']);
    }
}
