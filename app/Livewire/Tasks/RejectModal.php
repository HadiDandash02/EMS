<?php

namespace App\Livewire\Tasks;

use App\Exceptions\InvalidTaskTransitionException;
use App\Models\Task;
use App\Services\TaskWorkflowService;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/** Collects the mandatory rejection reason. Opened with $dispatch('open-reject-modal', { taskId }) */
class RejectModal extends Component
{
    public bool $open = false;

    #[Locked]
    public ?int $taskId = null;

    public string $reason = '';

    #[Locked]
    public string $taskTitle = '';

    protected function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:5', 'max:1000']];
    }

    protected function messages(): array
    {
        return ['reason.required' => 'Please explain why this task is being rejected.'];
    }

    #[On('open-reject-modal')]
    public function openModal(int $taskId): void
    {
        $task = Task::findOrFail($taskId);
        $this->authorize('reject', $task);

        $this->resetValidation();
        $this->reason = '';
        $this->taskId = $task->id;
        $this->taskTitle = $task->title;
        $this->open = true;
    }

    public function reject(TaskWorkflowService $workflow): void
    {
        $this->validate();

        try {
            $workflow->reject(Task::findOrFail($this->taskId), auth()->user(), $this->reason);
        } catch (AuthorizationException|InvalidTaskTransitionException $e) {
            $this->open = false;
            $this->dispatch('notify', type: 'error', message: $e->getMessage() ?: 'Not allowed.');

            return;
        }

        $this->open = false;
        $this->dispatch('notify', type: 'success', message: 'Task rejected.');
        $this->dispatch('task-updated');
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function render()
    {
        return view('livewire.tasks.reject-modal');
    }
}
