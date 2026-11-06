<?php

namespace App\Livewire\Approvals;

use App\Enums\TaskAction;
use App\Livewire\Concerns\HasFilterBar;
use App\Models\TaskHistory;
use App\Support\Sql;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Managers see the decisions THEY made; HR / super admin see every decision. */
class ApprovalHistory extends Component
{
    use HasFilterBar, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $decision = '';

    protected function filterProperties(): array
    {
        return ['search', 'decision'];
    }

    public function render()
    {
        $user = auth()->user();
        $term = trim($this->search);

        $records = TaskHistory::query()
            ->with(['task.employee.user', 'task.employee.department', 'actor'])
            ->whereIn('action', [TaskAction::Approved, TaskAction::Rejected])
            ->when(! $user->canSeeAll(), fn ($q) => $q->where('actor_id', $user->id))
            ->when(TaskAction::tryFrom($this->decision), fn ($q, $a) => $q->where('action', $a))
            ->when($term !== '', fn (Builder $q) => $q->whereHas('task', fn (Builder $t) => Sql::whereContains($t, 'tasks.title', $term)))
            ->latest('id')
            ->paginate($this->rowsPerPage());

        return view('livewire.approvals.approval-history', ['records' => $records])
            ->layout('components.layouts.app', ['title' => 'Approval history']);
    }
}
