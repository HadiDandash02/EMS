<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Livewire\Concerns\HasFilterBar;
use App\Livewire\Concerns\SortsAndExports;
use App\Models\Department;
use App\Models\User;
use App\Support\Sql;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Super admin: the list of accounts (search, filters, sorting, pagination, export).
 * Create / edit / delete each open their own page (UserForm, DeleteUser).
 */
class UserManager extends Component
{
    use HasFilterBar, SortsAndExports, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** Picked from the searchable "User" filter. */
    #[Url(as: 'user', except: '')]
    public string $userId = '';

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
        return ['search', 'userId', 'roleFilter', 'departmentFilter'];
    }

    private function query(): Builder
    {
        Gate::authorize('manage-users');
        $term = trim($this->search);

        $query = User::query()
            ->with(['employee.department', 'employee.manager.user'])
            ->when($term !== '', fn (Builder $q) => $q->where(function (Builder $q) use ($term) {
                Sql::whereContains($q, 'users.name', $term);
                Sql::whereContains($q, 'users.username', $term, 'or');
                Sql::whereContains($q, 'users.email', $term, 'or');
                $q->orWhereHas('employee', fn (Builder $e) => Sql::whereContains($e, 'employees.employee_number', $term));
            }))
            ->when((int) $this->userId, fn (Builder $q, int $id) => $q->where('users.id', $id))
            ->when(UserRole::tryFrom($this->roleFilter), fn (Builder $q, UserRole $r) => $q->where('role', $r))
            ->when((int) $this->departmentFilter, fn (Builder $q, int $id) => $q->whereHas('employee', fn (Builder $e) => $e->where('department_id', $id)));

        [$field, $dir] = $this->activeSort();

        match ($field) {
            'name' => $query->orderBy('users.name', $dir),
            'username' => $query->orderBy('users.username', $dir),
            'role' => $query->orderBy('users.role', $dir),
            'department' => $query->orderBy(fn ($q) => $q->select('departments.name')->from('employees')
                ->join('departments', 'departments.id', '=', 'employees.department_id')->whereColumn('employees.user_id', 'users.id')->limit(1), $dir),
            'created' => $query->orderBy('users.created_at', $dir),
            default => $query->orderBy('users.name'),
        };

        return $query->orderBy('users.id');
    }

    protected function sortableFields(): array
    {
        return ['name', 'username', 'role', 'department', 'created'];
    }

    protected function exportQuery(): Builder
    {
        return $this->query();
    }

    protected function exportFilename(): string
    {
        return 'users';
    }

    protected function exportColumns(): array
    {
        return [
            __('Full name') => fn ($u) => $u->name,
            __('Username') => fn ($u) => $u->username,
            __('Email') => fn ($u) => $u->email,
            __('Phone') => fn ($u) => $u->phone,
            __('Role') => fn ($u) => $u->role->label(),
            __('Department') => fn ($u) => $u->employee?->department->name,
            __('Manager') => fn ($u) => $u->employee?->manager?->user->name,
            __('Employee number') => fn ($u) => $u->employee?->employee_number,
            __('Created') => fn ($u) => $u->created_at->format('d/m/Y'),
        ];
    }

    public function render()
    {
        return view('livewire.admin.user-manager', [
            'users' => $this->query()->paginate($this->rowsPerPage()),
            'departments' => Department::orderBy('name')->get(),
            'roles' => UserRole::cases(),
            // options of the searchable "User" filter
            'userOptions' => User::orderBy('name')->get(['id', 'name', 'username'])->mapWithKeys(fn (User $u) => [$u->id => $u->name.' (@'.$u->username.')']),
        ])->layout('components.layouts.app', ['title' => 'User management']);
    }
}
