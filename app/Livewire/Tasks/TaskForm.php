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
use Livewire\Component;

/**
 * Create / edit a task on its own page (/{role}/tasks/create and /{role}/tasks/{task}/edit).
 */
class TaskForm extends Component
{
    #[Locked]
    public ?int $taskId = null;

    public string $title = '';

    public string $description = '';

    public string $priority = 'medium';

    public ?string $due_date = null;

    /** Only used by the super admin, who creates tasks on behalf of an employee. */
    public string $employee_id = '';

    public function mount(?Task $task = null): void
    {
        if ($task?->exists) {
            $this->authorize('update', $task);

            $this->taskId = $task->id;
            $this->title = $task->title;
            $this->description = (string) $task->description;
            $this->priority = $task->priority->value;
            $this->due_date = $task->due_date?->toDateString();

            return;
        }

        $this->authorize(auth()->user()->isSuperAdmin() ? 'createFor' : 'create', Task::class);
        $this->priority = TaskPriority::Medium->value;
    }

    protected function rules(): array
    {
        $rules = TaskRules::fields();

        // Editing keeps the owner; only a NEW task made by the super admin needs one picked.
        if (! $this->taskId && auth()->user()->isSuperAdmin()) {
            $rules['employee_id'] = ['required', 'exists:employees,id'];
        }

        return $rules;
    }

    public function save(TaskWorkflowService $workflow)
    {
        $data = $this->validate();
        $data['description'] = $data['description'] ?: null;
        $data['due_date'] = $data['due_date'] ?: null;

        try {
            if ($this->taskId) {
                $task = $workflow->update(Task::findOrFail($this->taskId), auth()->user(), $data);
                $message = __('Task updated.');
            } else {
                $task = $workflow->create(auth()->user(), $data);   // employee_id is ignored unless the user is the super admin
                $message = __('Task created.');
            }
        } catch (AuthorizationException|InvalidTaskTransitionException $e) {
            session()->flash('error', $e->getMessage() ?: __('Not allowed.'));

            return $this->redirect($this->listUrl(), navigate: true);
        }

        session()->flash('success', $message);

        return $this->redirect($this->taskId ? route('tasks.show', $task) : $this->listUrl(), navigate: true);
    }

    private function listUrl(): string
    {
        return auth()->user()->canSeeAll() && ! auth()->user()->employee ? route('tasks.all') : route('tasks.index');
    }

    public function render()
    {
        $isAdmin = auth()->user()->isSuperAdmin();

        return view('livewire.tasks.task-form', [
            'priorities' => TaskPriority::cases(),
            'owners' => $isAdmin && ! $this->taskId
                ? Employee::with('user')->get()->sortBy('user.name')->mapWithKeys(fn ($e) => [$e->id => $e->user->name.' · '.$e->employee_number])
                : collect(),
            'isAdmin' => $isAdmin,
            'cancelUrl' => $this->taskId ? route('tasks.show', $this->taskId) : $this->listUrl(),
        ])->layout('components.layouts.app', ['title' => $this->taskId ? 'Edit task' : 'New task']);
    }
}
