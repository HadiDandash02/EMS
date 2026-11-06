<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Livewire\Concerns\HasFilterBar;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Support\Sql;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Super admin CRUD for accounts. Every public method re-checks the 'manage-users'
 * gate, because Livewire actions can be invoked from the browser regardless of the UI.
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

    // ---- form state ----
    public bool $formOpen = false;

    #[Locked]
    public ?int $userId = null;

    public string $name = '';

    public string $username = '';

    public string $email = '';

    public string $phone = '';

    public string $role = 'employee';

    public string $department_id = '';

    public string $manager_id = '';

    public string $job_title = '';

    public string $employee_number = '';

    public string $hired_at = '';

    public string $password = '';

    // ---- delete confirmation ----
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
        return ['search', 'roleFilter', 'departmentFilter'];
    }

    private function guard(): void
    {
        Gate::authorize('manage-users');
    }

    // ------------------------------------------------------------------ create / update

    public function create(): void
    {
        $this->guard();
        $this->resetForm();
        $this->formOpen = true;
    }

    public function edit(int $id): void
    {
        $this->guard();
        $user = User::with('employee')->findOrFail($id);

        $this->resetForm();
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->username = (string) $user->username;
        $this->email = $user->email;
        $this->phone = (string) $user->phone;
        $this->role = $user->role->value;

        if ($employee = $user->employee) {
            $this->department_id = (string) $employee->department_id;
            $this->manager_id = (string) $employee->manager_id;
            $this->job_title = $employee->job_title;
            $this->employee_number = $employee->employee_number;
            $this->hired_at = $employee->hired_at?->toDateString() ?? '';
        }

        $this->formOpen = true;
    }

    public function save(): void
    {
        $this->guard();

        $this->username = strtolower(trim($this->username));
        $this->email = strtolower(trim($this->email));

        $editing = $this->userId ? User::with('employee')->findOrFail($this->userId) : null;
        // On EDIT this comes from the stored account - never from the submitted role - so a
        // tampered request cannot promote a regular user to super admin.
        $isAdminAccount = $editing ? $editing->isSuperAdmin() : $this->role === UserRole::SuperAdmin->value;

        $this->validate($this->rules($editing, $isAdminAccount), [
            'username.regex' => __('Use 3-30 letters, numbers, dots or underscores.'),
            'department_id.required_unless' => __('Choose a department.'),
            'job_title.required_unless' => __('Enter a job title.'),
        ]);

        if ($editing) {
            $this->assertRoleChangeIsSafe($editing);
        }

        if ($this->manager_id !== '' && $editing?->employee && (int) $this->manager_id === $editing->employee->id) {
            throw ValidationException::withMessages(['manager_id' => __('A person cannot be their own manager.')]);
        }

        DB::transaction(function () use ($editing, $isAdminAccount) {
            $user = $editing ?? new User;
            $user->fill(['name' => $this->name, 'username' => $this->username, 'email' => $this->email, 'phone' => $this->phone ?: null]);

            if (! $editing) {
                $user->role = UserRole::from($this->role);          // role is never mass assigned
                $user->email_verified_at = now();
            } elseif (! $isAdminAccount) {
                $user->role = UserRole::from($this->role);
            }

            if ($this->password !== '') {      // blank on edit = keep the current password
                $user->password = $this->password;
            }

            $user->save();

            if (! $isAdminAccount) {
                Employee::updateOrCreate(['user_id' => $user->id], [
                    'department_id' => (int) $this->department_id,
                    'manager_id' => $this->manager_id !== '' ? (int) $this->manager_id : null,
                    'job_title' => $this->job_title,
                    'employee_number' => $this->employee_number !== '' ? $this->employee_number : $this->nextEmployeeNumber(),
                    'hired_at' => $this->hired_at !== '' ? $this->hired_at : null,
                ]);
            }
        });

        $this->formOpen = false;
        $this->dispatch('notify', type: 'success', message: $editing ? __('User updated.') : __('User created.'));
        $this->resetForm();
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(?User $editing, bool $isAdminAccount): array
    {
        $employeeId = $editing?->employee?->id;
        $allowedRoles = $editing ? array_map(fn ($r) => $r->value, array_filter(UserRole::cases(), fn ($r) => $r !== UserRole::SuperAdmin))
            : UserRole::values();
        $passwordRule = Password::min(8)->letters()->numbers();

        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'username' => ['required', 'regex:/^[a-z0-9_.]{3,30}$/', Rule::unique('users', 'username')->ignore($editing?->id)],
            'email' => ['required', 'email', 'max:191', Rule::unique('users', 'email')->ignore($editing?->id)],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]*$/'],
            'role' => $isAdminAccount && $editing ? ['required', Rule::in([UserRole::SuperAdmin->value])] : ['required', Rule::in($allowedRoles)],
            'department_id' => $isAdminAccount ? ['nullable'] : ['required', 'exists:departments,id'],
            'manager_id' => $isAdminAccount ? ['nullable'] : ['nullable', Rule::exists('employees', 'id')->where(
                fn ($q) => $q->whereIn('user_id', User::query()->where('role', UserRole::Manager)->select('id'))
            )],
            'job_title' => $isAdminAccount ? ['nullable'] : ['required', 'string', 'max:100'],
            'employee_number' => $isAdminAccount ? ['nullable'] : ['nullable', 'string', 'max:20', Rule::unique('employees', 'employee_number')->ignore($employeeId)],
            'hired_at' => $isAdminAccount ? ['nullable'] : ['nullable', 'date', 'before_or_equal:today'],
            'password' => $editing ? ['nullable', $passwordRule] : ['required', $passwordRule],
        ];
    }

    private function assertRoleChangeIsSafe(User $editing): void
    {
        if ($editing->isSuperAdmin() || $editing->role->value === $this->role) {
            return;
        }

        if ($editing->role === UserRole::Manager && $editing->employee?->directReports()->exists()) {
            throw ValidationException::withMessages([
                'role' => __('This manager still has people reporting to them. Reassign their team first.'),
            ]);
        }
    }

    private function nextEmployeeNumber(): string
    {
        $next = (int) Employee::max('id') + 1;

        do {
            $number = sprintf('EMP-%04d', $next++);
        } while (Employee::where('employee_number', $number)->exists());

        return $number;
    }

    // ------------------------------------------------------------------ delete

    public function confirmDelete(int $id): void
    {
        $this->guard();
        $user = User::findOrFail($id);

        $this->deleteId = $user->id;
        $this->deleteName = $user->name;
    }

    public function cancelDelete(): void
    {
        $this->deleteId = null;
    }

    public function delete(): void
    {
        $this->guard();

        $user = User::with('employee')->findOrFail($this->deleteId);

        if ($user->is(auth()->user())) {
            $this->failDelete(__('You cannot delete your own account.'));

            return;
        }

        $tasks = $user->employee?->tasks()->count() ?? 0;
        if ($tasks > 0) {
            $this->failDelete(__(':name owns :count task(s). Tasks are part of the approval audit trail, so this account cannot be deleted.', ['name' => $user->name, 'count' => $tasks]));

            return;
        }

        if ($user->employee?->directReports()->exists()) {
            $this->failDelete(__(':name still manages other employees. Reassign their team first.', ['name' => $user->name]));

            return;
        }

        DB::transaction(function () use ($user) {
            if ($user->avatar_path) {
                Storage::disk('local')->delete($user->avatar_path);
            }
            $user->delete(); // employee profile cascades
        });

        $this->deleteId = null;
        $this->dispatch('notify', type: 'success', message: __(':name was deleted.', ['name' => $user->name]));
    }

    private function failDelete(string $message): void
    {
        $this->deleteId = null;
        $this->dispatch('notify', type: 'error', message: $message);
    }

    // ------------------------------------------------------------------ misc

    public function close(): void
    {
        $this->formOpen = false;
    }

    private function resetForm(): void
    {
        $this->resetValidation();
        $this->reset('userId', 'name', 'username', 'email', 'phone', 'department_id', 'manager_id', 'job_title', 'employee_number', 'hired_at', 'password');
        $this->role = UserRole::Employee->value;
    }

    public function render()
    {
        $this->guard();
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
            ->paginate(10);

        return view('livewire.admin.user-manager', [
            'users' => $users,
            'departments' => Department::orderBy('name')->get(),
            'managers' => Employee::with('user')->whereHas('user', fn ($q) => $q->where('role', UserRole::Manager))->get()->sortBy('user.name'),
            'roles' => UserRole::cases(),
        ])->layout('components.layouts.app', ['title' => 'User management']);
    }
}
