<?php

namespace App\Livewire\Tasks;

use App\Livewire\Concerns\HandlesTaskActions;
use App\Models\Task;
use Livewire\Attributes\On;
use Livewire\Component;

class TaskShow extends Component
{
    use HandlesTaskActions;

    public Task $task;

    public function mount(Task $task): void
    {
        // Server-side guard: changing the ID in the URL gives a 403, not someone else's data.
        $this->authorize('view', $task);

        $this->task = $task;
    }

    #[On('task-updated')]
    #[On('task-saved')]
    public function refreshTask(): void
    {
        $this->task->refresh();
    }

    public function render()
    {
        $this->task->load(['employee.user', 'employee.department', 'employee.manager.user', 'reviewer', 'histories.actor']);

        return view('livewire.tasks.task-show')
            ->layout('components.layouts.app', ['title' => 'Task details']);
    }
}
