<?php

namespace App\Livewire\Concerns;

use App\Exceptions\InvalidTaskTransitionException;
use App\Models\Task;
use App\Services\TaskWorkflowService;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * One-click task actions available directly on a list / detail page.
 *
 * Only "start / rework" needs no confirmation. Complete, approve, cancel, delete and reject each open
 * their own page (Tasks\ConfirmAction, Tasks\RejectTask) - there are no popups.
 *
 * SECURITY NOTE: every public method of a Livewire component can be invoked from the browser with any
 * argument, so hiding a button is NOT protection. The task is loaded by id and handed to
 * TaskWorkflowService, which authorises the logged-in user against the (locked) row on the server.
 */
trait HandlesTaskActions
{
    public function startTask(int $taskId): void
    {
        $task = Task::findOrFail($taskId);

        try {
            app(TaskWorkflowService::class)->start($task, auth()->user());
            $this->notify(__('Task started.'));
            $this->dispatch('task-updated');
        } catch (AuthorizationException $e) {
            $this->notify($e->getMessage() ?: __('You are not allowed to do that.'), 'error');
        } catch (InvalidTaskTransitionException $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    /** Shown by the inline alert bar at the top of the page (see the layout). */
    protected function notify(string $message, string $type = 'success'): void
    {
        $this->dispatch('notify', type: $type, message: $message);
    }
}
