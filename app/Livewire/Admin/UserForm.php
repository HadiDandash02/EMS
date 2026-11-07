<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Support\PasswordPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Super admin: create or edit an account on its own page
 * (/admin/users/create and /admin/users/{user}/edit). Gate 'manage-users' is checked on open and on save.
 */
class UserForm extends Component
{
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

    public function mount(?User $user = null): void
    {
        Gate::authorize('manage-users');

        if (! $user?->exists) {
            return;
        }

        $user->loadMissing('employee');

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
    }

    public function save()
    {
        Gate::authorize('manage-users');

        $this->username = strtolower(trim($this->username));
        $this->email = strtolower(trim($this->email));

        $editing = $this->userId ? User::with('employee')->findOrFail($this->userId) : null;
        // On EDIT this comes from the stored account - never from the submitted role - so a
        // tampered request cannot promote a regular user to super admin.
        $isAdminAccount = $editing ? $editing->isSuperAdmin() : $this->role === UserRole::SuperAdmin->value;

        $this->validate($this->rules($editing, $isAdminAccount), [
            'username.regex' => __('Use 3-30 letters, numbers, dots or underscores.'),
            'department_id.required' => __('Choose a department.'),
            'job_title.required' => __('Enter a job title.'),
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

        session()->flash('success', $editing ? __('User updated.') : __('User created.'));

        return $this->redirect(route('admin.users'), navigate: true);
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(?User $editing, bool $isAdminAccount): array
    {
        $employeeId = $editing?->employee?->id;
        $allowedRoles = $editing
            ? array_map(fn ($r) => $r->value, array_filter(UserRole::cases(), fn ($r) => $r !== UserRole::SuperAdmin))
            : UserRole::values();

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
            // Strong password whenever one is typed (required for a new account, optional when editing).
            'password' => $editing ? ['nullable', PasswordPolicy::rule()] : ['required', PasswordPolicy::rule()],
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

    public function render()
    {
        Gate::authorize('manage-users');

        $subject = $this->userId ? User::with('employee.department', 'employee.manager.user')->find($this->userId) : null;

        return view('livewire.admin.user-form', [
            'subject' => $subject,
            'taskCount' => $subject?->employee?->tasks()->count() ?? 0,
            'departments' => Department::orderBy('name')->get(),
            'managers' => Employee::with('user')->whereHas('user', fn ($q) => $q->where('role', UserRole::Manager))
                ->when($this->userId, fn ($q) => $q->where('user_id', '!=', $this->userId))->get()->sortBy('user.name'),
            'roles' => UserRole::cases(),
        ])->layout('components.layouts.app', ['title' => $this->userId ? 'Edit user' : 'New user']);
    }
}
