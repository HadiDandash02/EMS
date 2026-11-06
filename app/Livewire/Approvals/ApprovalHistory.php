<?php

namespace App\Livewire\Approvals;

use App\Enums\TaskAction;
use App\Models\TaskHistory;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Managers see the decisions THEY made; HR sees every decision. */
class ApprovalHistory extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $decision = '';

    public function updatedDecision(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();

        $records = TaskHistory::query()
            ->with(['task.employee.user', 'task.employee.department', 'actor'])
            ->whereIn('action', [TaskAction::Approved, TaskAction::Rejected])
            ->when(! $user->canSeeAll(), fn ($q) => $q->where('actor_id', $user->id))
            ->when(TaskAction::tryFrom($this->decision), fn ($q, $a) => $q->where('action', $a))
            ->latest('id')
            ->paginate(10);

        return view('livewire.approvals.approval-history', ['records' => $records])
            ->layout('components.layouts.app', ['title' => 'Approval history']);
    }
}
