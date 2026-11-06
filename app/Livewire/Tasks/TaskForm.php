<?php

namespace App\Livewire\Tasks;

use App\Enums\TaskPriority;
use App\Exceptions\InvalidTaskTransitionException;
use App\Models\Employee;
use App\Models\Task;
use App\Services\TaskWorkflowService;
use App\Support\TaskRules;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Create / edit modal. Opened from anywhere with: $dispatch('open-task-form', { taskId: 12 })
 * and announces success with the `task-saved` event that lists listen to.
 */
class TaskForm extends Component
{
    public bool $open = false;

    #[Locked]
    public ?int $taskId = null;

    public string $title = '';

    public string $description = '';

    public string $priority = 'medium';

    public ?string $due_date = null;

    /** Only used by the super admin, who creates tasks on behalf of an employee. */
    public string $employee_id = '';

    protected function rules(): array
    {
        $rules = TaskRules::fields();

        // Editing keeps the owner; only a NEW task made by the super admin needs one picked.
        if (! $this->taskId && auth()->user()->isSuperAdmin()) {
            $rules['employee_id'] = ['required', 'exists:employees,id'];
        }

        return $rules;
    }

    #[On('open-task-form')]
    public function openForm(?int $taskId = null): void
    {
        $this->resetValidation();
        $this->reset('title', 'description', 'due_date', 'employee_id');
        $this->priority = TaskPriority::Medium->value;
        $this->taskId = $taskId;

        if ($taskId) {
            $task = Task::findOrFail($taskId);
            $this->authorize('update', $task);

            $this->title = $task->title;
            $this->description = (string) $task->description;
            $this->priority = $task->priority->value;
            $this->due_date = $task->due_date?->toDateString();
        } else {
            $this->authorize(auth()->user()->isSuperAdmin() ? 'createFor' : 'create', Task::class);
        }

        $this->open = true;
    }

    public function save(TaskWorkflowService $workflow): void
    {
        $data = $this->validate();
        $data['description'] = $data['description'] ?: null;
        $data['due_date'] = $data['due_date'] ?: null;

        try {
            // (employee_id is only present for the super admin; the service ignores it for everyone else)
            if ($this->taskId) {
                $workflow->update(Task::findOrFail($this->taskId), auth()->user(), $data);
                $message = 'Task updated.';
            } else {
                $workflow->create(auth()->user(), $data);
                $message = 'Task created.';
            }
        } catch (AuthorizationException|InvalidTaskTransitionException $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage() ?: 'Not allowed.');
            $this->open = false;

            return;
        }

        $this->open = false;
        $this->dispatch('notify', type: 'success', message: $message);
        $this->dispatch('task-saved');
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function render()
    {
        $isAdmin = auth()->check() && auth()->user()->isSuperAdmin();

        return view('livewire.tasks.task-form', [
            'priorities' => TaskPriority::cases(),
            'owners' => $isAdmin && $this->open && ! $this->taskId
                ? Employee::with('user')->get()->sortBy('user.name')->mapWithKeys(fn ($e) => [$e->id => $e->user->name.' · '.$e->employee_number])
                : collect(),
            'isAdmin' => $isAdmin,
        ]);
    }
}
