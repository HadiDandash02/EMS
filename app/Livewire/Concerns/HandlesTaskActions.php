<?php

namespace App\Livewire\Concerns;

use App\Exceptions\InvalidTaskTransitionException;
use App\Models\Task;
use App\Services\TaskWorkflowService;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Task actions shared by the list and the detail page.
 *
 * SECURITY NOTE: every public method of a Livewire component can be invoked from the
 * browser with any argument. Hiding a button is therefore NOT protection. Each action
 * loads the task by id and hands it to TaskWorkflowService, which authorises the
 * logged-in user against the (locked) row on the server.
 */
trait HandlesTaskActions
{
    public function startTask(int $taskId): void
    {
        $this->performTaskAction($taskId, fn (TaskWorkflowService $s, Task $t) => $s->start($t, auth()->user()), 'Task started.');
    }

    public function completeTask(int $taskId): void
    {
        $this->performTaskAction($taskId, fn (TaskWorkflowService $s, Task $t) => $s->complete($t, auth()->user()), 'Task marked as completed.');
    }

    public function cancelTask(int $taskId): void
    {
        $this->performTaskAction($taskId, fn (TaskWorkflowService $s, Task $t) => $s->cancel($t, auth()->user()), 'Task cancelled.');
    }

    public function approveTask(int $taskId): void
    {
        $this->performTaskAction($taskId, fn (TaskWorkflowService $s, Task $t) => $s->approve($t, auth()->user()), 'Task approved.');
    }

    /** @param  Closure(TaskWorkflowService, Task): mixed  $action */
    private function performTaskAction(int $taskId, Closure $action, string $successMessage): void
    {
        $task = Task::findOrFail($taskId);

        try {
            $action(app(TaskWorkflowService::class), $task);
            $this->notify($successMessage);
            $this->dispatch('task-updated');
        } catch (AuthorizationException $e) {
            $this->notify($e->getMessage() ?: 'You are not allowed to do that.', 'error');
        } catch (InvalidTaskTransitionException $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    protected function notify(string $message, string $type = 'success'): void
    {
        $this->dispatch('notify', type: $type, message: $message);
    }
}
