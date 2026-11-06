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
            $user->role === UserRole::Hr => Response::allow(),
            $task->isOwnedBy($user) => Response::allow(),
            $this->managesOwnerOf($user, $task) => Response::allow(),
            default => Response::deny('You are not allowed to view this task.'),
        };
    }

    public function create(User $user): bool
    {
        return $user->employee !== null;
    }

    public function update(User $user, Task $task): Response
    {
        return $this->ownerOnly($user, $task, 'You can only edit your own tasks.');
    }

    public function start(User $user, Task $task): Response
    {
        return $this->ownerOnly($user, $task, 'You can only start your own tasks.');
    }

    public function complete(User $user, Task $task): Response
    {
        return $this->ownerOnly($user, $task, 'You can only complete your own tasks.');
    }

    public function cancel(User $user, Task $task): Response
    {
        return $this->ownerOnly($user, $task, 'You can only cancel your own tasks.');
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
        if ($user->role !== UserRole::Manager) {
            return Response::deny('Only managers can approve or reject tasks.');
        }

        if ($task->isOwnedBy($user)) {
            return Response::deny('You cannot review your own task.');
        }

        if (! $this->managesOwnerOf($user, $task)) {
            return Response::deny('This task belongs to an employee outside your team.');
        }

        return Response::allow();
    }

    private function managesOwnerOf(User $user, Task $task): bool
    {
        if ($user->role !== UserRole::Manager || $user->employee === null) {
            return false;
        }

        $task->loadMissing('employee');

        return $task->employee->manager_id === $user->employee->id;
    }
}
