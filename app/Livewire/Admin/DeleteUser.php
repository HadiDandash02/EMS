<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** "Delete this user?" on its own page (/admin/users/{user}/delete). Re-checked when confirmed. */
class DeleteUser extends Component
{
    #[Locked]
    public int $userId;

    public function mount(User $user)
    {
        Gate::authorize('manage-users');

        if ($problem = $this->problem($user)) {
            session()->flash('error', $problem);

            return $this->redirect(route('admin.users'), navigate: true);
        }

        $this->userId = $user->id;
    }

    /** Why this account cannot be deleted (null = it can). */
    private function problem(User $user): ?string
    {
        $user->loadMissing('employee');

        if ($user->is(auth()->user())) {
            return __('You cannot delete your own account.');
        }

        $tasks = $user->employee?->tasks()->count() ?? 0;
        if ($tasks > 0) {
            return __(':name owns :count task(s). Tasks are part of the approval audit trail, so this account cannot be deleted.', ['name' => $user->name, 'count' => $tasks]);
        }

        if ($user->employee?->directReports()->exists()) {
            return __(':name still manages other employees. Reassign their team first.', ['name' => $user->name]);
        }

        return null;
    }

    public function delete()
    {
        Gate::authorize('manage-users');

        $user = User::with('employee')->findOrFail($this->userId);

        if ($problem = $this->problem($user)) {
            session()->flash('error', $problem);

            return $this->redirect(route('admin.users'), navigate: true);
        }

        DB::transaction(function () use ($user) {
            if ($user->avatar_path) {
                Storage::disk('local')->delete($user->avatar_path);
            }
            $user->delete(); // employee profile cascades
        });

        session()->flash('success', __(':name was deleted.', ['name' => $user->name]));

        return $this->redirect(route('admin.users'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.delete-user', ['user' => User::with('employee.department')->findOrFail($this->userId)])
            ->layout('components.layouts.app', ['title' => 'Delete user']);
    }
}
