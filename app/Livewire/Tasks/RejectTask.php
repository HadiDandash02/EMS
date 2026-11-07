<?php

namespace App\Livewire\Tasks;

use App\Enums\TaskStatus;
use App\Exceptions\InvalidTaskTransitionException;
use App\Models\Task;
use App\Services\TaskWorkflowService;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Reject a completed task on its own page; the reason is mandatory. (/{role}/tasks/{task}/reject) */
class RejectTask extends Component
{
    #[Locked]
    public int $taskId;

    public string $reason = '';

    public function mount(Task $task)
    {
        $this->authorize('reject', $task);   // 403 for anyone who may not review this task

        if ($task->status !== TaskStatus::Completed) {
            session()->flash('error', __('Only completed tasks can be rejected (this task is :status).', ['status' => $task->status->label()]));

            return $this->redirect(route('tasks.show', $task), navigate: true);
        }

        $this->taskId = $task->id;
    }

    protected function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:5', 'max:1000']];
    }

    protected function messages(): array
    {
        return ['reason.required' => __('Please explain why this task is being rejected.')];
    }

    public function reject(TaskWorkflowService $workflow)
    {
        $this->validate();
        $task = Task::findOrFail($this->taskId);

        try {
            $workflow->reject($task, auth()->user(), $this->reason);
        } catch (AuthorizationException|InvalidTaskTransitionException $e) {
            session()->flash('error', $e->getMessage() ?: __('Not allowed.'));

            return $this->redirect(route('tasks.show', $task), navigate: true);
        }

        session()->flash('success', __('Task rejected.'));

        return $this->redirect(route('team.tasks'), navigate: true);
    }

    public function render()
    {
        return view('livewire.tasks.reject-task', [
            'task' => Task::with('employee.user', 'employee.department')->findOrFail($this->taskId),
        ])->layout('components.layouts.app', ['title' => 'Reject task']);
    }
}
