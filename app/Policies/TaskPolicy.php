<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * WHO may do something. (WHAT STATE the task is in is enforced by TaskWorkflowService,
 * because it must be re-checked inside the database transaction.)
 */
class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->employee !== null;
    }

    public function view(User $user, Task $task): Response
    {
        return match (true) {
            $user->isSuperAdmin() => Response::allow(),
            $task->isOwnedBy($user) => Response::allow(),
            $this->hrOfDepartment($user, $task) => Response::allow(),
            $this->managesOwnerOf($user, $task) => Response::allow(),
            default => Response::deny($this->inAnotherDepartment($user, $task) ? __('This task belongs to another department.') : __('You are not allowed to view this task.')),
        };
    }

    public function create(User $user): bool
    {
        return $user->employee !== null;
    }

    /** The super admin may create a task on behalf of any employee. */
    public function createFor(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Task $task): Response
    {
        return $user->isSuperAdmin() || $task->isOwnedBy($user)
            ? Response::allow()
            : Response::deny(__('You can only edit your own tasks.'));
    }

    public function delete(User $user, Task $task): Response
    {
        return $user->isSuperAdmin() || $task->isOwnedBy($user)
            ? Response::allow()
            : Response::deny(__('You can only delete your own tasks.'));
    }

    public function start(User $user, Task $task): Response
    {
        return $this->ownerOnly($user, $task, __('You can only start your own tasks.'));
    }

    public function complete(User $user, Task $task): Response
    {
        return $this->ownerOnly($user, $task, __('You can only complete your own tasks.'));
    }

    public function cancel(User $user, Task $task): Response
    {
        return $this->ownerOnly($user, $task, __('You can only cancel your own tasks.'));
    }

    public function approve(User $user, Task $task): Response
    {
        return $this->review($user, $task);
    }

    public function reject(User $user, Task $task): Response
    {
        return $this->review($user, $task);
    }

    private function ownerOnly(User $user, Task $task, string $message): Response
    {
        return $task->isOwnedBy($user) ? Response::allow() : Response::deny($message);
    }

    private function review(User $user, Task $task): Response
    {
        // The super admin holds the manager and HR powers for EVERY department and user: he may approve or reject any
        // task. (The status workflow still applies: only COMPLETED tasks, never twice, a reason is required to reject.)
        if ($user->isSuperAdmin()) {
            return Response::allow();
        }

        // [CHANGE-REQUEST #1: HR can approve and reject - own department] UNCOMMENT this block (and delete the old test
        //   test_hr_cannot_approve_tasks in ApprovalWorkflowTest; the new tests are commented there).
        // if ($user->isHr()) {
        //     if ($task->isOwnedBy($user)) {
        //         return Response::deny(__('You cannot review your own task.'));
        //     }
        //
        //     return $this->inAnotherDepartment($user, $task)
        //         ? Response::deny(__('This task belongs to another department.'))
        //         : Response::allow();      // for ANY department: return Response::allow(); and also widen User::confinedDepartmentId()
        // }

        if (! $user->hasRole(UserRole::Manager)) {
            return Response::deny(__('Only managers and the super admin can approve or reject tasks.'));
        }

        if ($task->isOwnedBy($user)) {
            return Response::deny(__('You cannot review your own task.'));
        }

        if ($this->inAnotherDepartment($user, $task)) {
            return Response::deny(__('This task belongs to another department.'));
        }

        if (! $this->managesOwnerOf($user, $task)) {
            return Response::deny(__('This task belongs to an employee outside your team.'));
        }

        return Response::allow();
    }

    private function managesOwnerOf(User $user, Task $task): bool
    {
        if (! $user->hasRole(UserRole::Manager) || $user->employee === null) {
            return false;
        }

        $task->loadMissing('employee');

        // the team is made of direct reports AND they must work in the manager's own department
        return $task->employee->manager_id === $user->employee->id
            && $task->employee->department_id === $user->employee->department_id;
    }

    /** HR sees (and reviews information of) every task of their own department. */
    private function hrOfDepartment(User $user, Task $task): bool
    {
        if (! $user->isHr() || $user->employee === null) {
            return false;
        }

        if ($user->confinedDepartmentId() === null) {
            return true;   // company-wide HR
        }

        $task->loadMissing('employee');

        return $task->employee->department_id === $user->employee->department_id;
    }

    /** True when a manager/HR user is looking at a task of a department that is not theirs. */
    private function inAnotherDepartment(User $user, Task $task): bool
    {
        $department = $user->confinedDepartmentId();

        if ($department === null) {
            return false;
        }

        $task->loadMissing('employee');

        return $task->employee->department_id !== $department;
    }
}
