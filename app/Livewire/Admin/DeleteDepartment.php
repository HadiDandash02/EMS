<?php

namespace App\Livewire\Admin;

use App\Models\Department;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** "Delete this department?" on its own page (/admin/departments/{id}/delete). */
class DeleteDepartment extends Component
{
    #[Locked]
    public int $departmentId;

    public function mount(Department $department)
    {
        Gate::authorize('manage-users');

        if ($problem = $this->problem($department)) {
            session()->flash('error', $problem);

            return $this->redirect(route('admin.departments'), navigate: true);
        }

        $this->departmentId = $department->id;
    }

    private function problem(Department $department): ?string
    {
        $count = $department->employees()->count();

        return $count > 0
            ? __(':name still has :count employee(s). Move them first.', ['name' => $department->name, 'count' => $count])
            : null;
    }

    public function delete()
    {
        Gate::authorize('manage-users');

        $department = Department::findOrFail($this->departmentId);

        if ($problem = $this->problem($department)) {
            session()->flash('error', $problem);

            return $this->redirect(route('admin.departments'), navigate: true);
        }

        $department->delete();
        session()->flash('success', __(':name was deleted.', ['name' => $department->name]));

        return $this->redirect(route('admin.departments'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.delete-department', ['department' => Department::withCount('employees')->findOrFail($this->departmentId)])
            ->layout('components.layouts.app', ['title' => 'Delete department']);
    }
}
