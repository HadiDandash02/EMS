<?php

namespace App\Livewire\Approvals;

use App\Enums\TaskAction;
use App\Livewire\Concerns\HasFilterBar;
use App\Livewire\Concerns\SortsAndExports;
use App\Models\TaskHistory;
use App\Support\Sql;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Managers see the decisions THEY made; HR / super admin see every decision. */
class ApprovalHistory extends Component
{
    use HasFilterBar, SortsAndExports, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** Picked from the searchable "Task" filter. */
    #[Url(as: 'task')]
    public $taskId = [];          // list of ids (multi-select)

    #[Url]
    public $decision = [];        // list: approved / rejected

    protected function filterProperties(): array
    {
        return ['search', 'taskId', 'decision'];
    }

    private function query(): Builder
    {
        $user = auth()->user();
        $term = trim($this->search);

        $query = TaskHistory::query()
            ->with(['task.employee.user', 'task.employee.department', 'actor'])
            ->whereIn('action', [TaskAction::Approved, TaskAction::Rejected])
            ->when(! $user->canSeeAll(), fn ($q) => $q->where('actor_id', $user->id))
            ->when($this->ids($this->taskId), fn ($q, array $ids) => $q->whereIn('task_histories.task_id', $ids))
            ->when(array_values(array_intersect($this->enumValues($this->decision, TaskAction::class), ['approved', 'rejected'])), fn ($q, array $a) => $q->whereIn('action', $a))
            ->when($term !== '', fn (Builder $q) => $q->whereHas('task', fn (Builder $t) => Sql::whereContains($t, 'tasks.title', $term)));

        [$field, $dir] = $this->activeSort();

        match ($field) {
            'decision' => $query->orderBy('task_histories.action', $dir),
            'task' => $query->orderBy(fn ($q) => $q->select('tasks.title')->from('tasks')->whereColumn('tasks.id', 'task_histories.task_id')->limit(1), $dir),
            'employee' => $query->orderBy(fn ($q) => $q->select('users.name')->from('tasks')
                ->join('employees', 'employees.id', '=', 'tasks.employee_id')->join('users', 'users.id', '=', 'employees.user_id')
                ->whereColumn('tasks.id', 'task_histories.task_id')->limit(1), $dir),
            'by' => $query->orderBy(fn ($q) => $q->select('users.name')->from('users')->whereColumn('users.id', 'task_histories.actor_id')->limit(1), $dir),
            'date' => $query->orderBy('task_histories.created_at', $dir),
            default => $query->latest('task_histories.id'),
        };

        return $query->orderByDesc('task_histories.id');
    }

    protected function sortableFields(): array
    {
        return ['decision', 'task', 'employee', 'by', 'date'];
    }

    protected function exportQuery(): Builder
    {
        return $this->query();
    }

    protected function exportFilename(): string
    {
        return 'approval-history';
    }

    protected function exportColumns(): array
    {
        return [
            __('Decision') => fn ($r) => $r->to_status->label(),
            __('Task') => fn ($r) => $r->task->title,
            __('Employee') => fn ($r) => $r->task->employee->user->name,
            __('Decided by') => fn ($r) => $r->actor?->name,
            __('Reason') => fn ($r) => $r->reason,
            __('Date') => fn ($r) => $r->created_at->format('d/m/Y H:i'),
        ];
    }

    public function render()
    {
        $user = auth()->user();

        // options of the searchable "Task" filter: tasks that have a decision this user is allowed to see
        $taskIds = TaskHistory::query()->whereIn('action', [TaskAction::Approved, TaskAction::Rejected])
            ->when(! $user->canSeeAll(), fn ($q) => $q->where('actor_id', $user->id))->pluck('task_id')->unique();
        $taskOptions = \App\Models\Task::with('employee.user:id,name')->whereIn('id', $taskIds)->latest('id')->get()
            ->mapWithKeys(fn ($t) => [$t->id => $t->title.' — '.$t->employee->user->name]);

        return view('livewire.approvals.approval-history', ['records' => $this->query()->paginate($this->rowsPerPage()), 'taskOptions' => $taskOptions])
            ->layout('components.layouts.app', ['title' => 'Approval history']);
    }
}
