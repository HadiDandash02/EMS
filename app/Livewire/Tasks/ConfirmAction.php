<?php

namespace App\Livewire\Tasks;

use App\Enums\TaskStatus;
use App\Exceptions\InvalidTaskTransitionException;
use App\Models\Task;
use App\Services\TaskWorkflowService;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * "Are you sure?" on its own page instead of a popup:
 * /{role}/tasks/{task}/confirm/{complete|approve|cancel|delete}
 *
 * Authorisation is checked when the page opens AND again inside TaskWorkflowService when confirmed.
 */
class ConfirmAction extends Component
{
    #[Locked]
    public int $taskId;

    #[Locked]
    public string $action;

    public function mount(Task $task, string $action)
    {
        abort_unless(array_key_exists($action, $this->definitions()), 404);

        $this->authorize($this->definitions()[$action]['ability'], $task);   // 403 when the user may not do this

        if (! $this->isAvailable($task, $action)) {
            session()->flash('error', __('This task is :status, so this action is not available.', ['status' => $task->status->label()]));

            return $this->redirect(route('tasks.show', $task), navigate: true);
        }

        $this->taskId = $task->id;
        $this->action = $action;
    }

    /** @return array<string, array{ability:string,title:string,text:string,button:string,variant:string,icon:string,done:string}> */
    private function definitions(): array
    {
        return [
            'complete' => ['ability' => 'complete', 'title' => 'Complete task', 'text' => 'Mark this task as completed and send it for approval?', 'button' => 'Complete task', 'variant' => 'primary', 'icon' => 'check', 'done' => 'Task marked as completed.'],
            'approve' => ['ability' => 'approve', 'title' => 'Approve task', 'text' => 'Approve this task?', 'button' => 'Approve task', 'variant' => 'success', 'icon' => 'check', 'done' => 'Task approved.'],
            'cancel' => ['ability' => 'cancel', 'title' => 'Cancel task', 'text' => 'Cancel this task? This cannot be undone.', 'button' => 'Cancel task', 'variant' => 'warning', 'icon' => 'ban', 'done' => 'Task cancelled.'],
            'delete' => ['ability' => 'delete', 'title' => 'Delete task', 'text' => 'Delete this task permanently? Its history will be deleted too.', 'button' => 'Delete task', 'variant' => 'danger', 'icon' => 'trash', 'done' => 'Task deleted.'],
        ];
    }

    /** Same state rules as TaskWorkflowService (which enforces them again, atomically). */
    private function isAvailable(Task $task, string $action): bool
    {
        return match ($action) {
            'complete' => in_array($task->status, [TaskStatus::Pending, TaskStatus::InProgress], true),
            'approve' => $task->status === TaskStatus::Completed,
            'cancel' => $task->status === TaskStatus::Pending,
            'delete' => auth()->user()->isSuperAdmin() || in_array($task->status, [TaskStatus::Pending, TaskStatus::Cancelled], true),
        };
    }

    public function confirm(TaskWorkflowService $workflow)
    {
        $task = Task::findOrFail($this->taskId);
        $user = auth()->user();

        try {
            match ($this->action) {
                'complete' => $workflow->complete($task, $user),
                'approve' => $workflow->approve($task, $user),
                'cancel' => $workflow->cancel($task, $user),
                'delete' => $workflow->delete($task, $user),
            };
        } catch (AuthorizationException|InvalidTaskTransitionException $e) {
            session()->flash('error', $e->getMessage() ?: __('Not allowed.'));

            return $this->redirect($this->action === 'delete' ? $this->listUrl() : route('tasks.show', $task), navigate: true);
        }

        session()->flash('success', __($this->definitions()[$this->action]['done']));

        return $this->redirect($this->redirectAfterSuccess($task), navigate: true);
    }

    /** After a decision the manager goes straight back to the team list; after delete, to the owner's list. */
    private function redirectAfterSuccess(Task $task): string
    {
        return match ($this->action) {
            'delete' => $this->listUrl(),
            'approve' => $this->afterDecisionUrl(),
            default => route('tasks.show', $task),
        };
    }

    /** A manager returns to the team list; the super admin (who has no team) to the list of all tasks. */
    private function afterDecisionUrl(): string
    {
        return auth()->user()->isManager() ? route('team.tasks') : route('tasks.all');
    }

    private function listUrl(): string
    {
        // Delete is only possible for the owner (own list) or the super admin (company list).
        return auth()->user()->isSuperAdmin() ? route('tasks.all') : route('tasks.index');
    }

    public function render()
    {
        $def = $this->definitions()[$this->action];

        return view('livewire.tasks.confirm-action', [
            'task' => Task::with('employee.user', 'employee.department')->findOrFail($this->taskId),
            'def' => $def,
        ])->layout('components.layouts.app', ['title' => $def['title']]);
    }
}
