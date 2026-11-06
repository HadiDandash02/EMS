<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Exceptions\InvalidTaskTransitionException;
use App\Livewire\Tasks\ConfirmAction;
use App\Livewire\Tasks\RejectTask;
use App\Models\Task;
use App\Models\TaskHistory;
use App\Services\TaskWorkflowService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Concerns\BuildsOrganisation;
use Tests\TestCase;

class ApprovalWorkflowTest extends TestCase
{
    use BuildsOrganisation, RefreshDatabase;

    private TaskWorkflowService $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrganisation();
        $this->workflow = app(TaskWorkflowService::class);
    }

    public function test_manager_can_approve_an_authorized_completed_task(): void
    {
        $task = $this->taskFor($this->employeeEmp, 'completed');

        Livewire::actingAs($this->manager)
            ->test(ConfirmAction::class, ['task' => $task, 'action' => 'approve'])
            ->call('confirm')
            ->assertRedirect(route('tasks.show', $task));

        $this->assertSame('Task approved.', session('success'));

        $task->refresh();
        $this->assertSame(TaskStatus::Approved, $task->status);
        $this->assertSame($this->manager->id, $task->reviewed_by);
        $this->assertNotNull($task->reviewed_at);
        $this->assertDatabaseHas('task_histories', [
            'task_id' => $task->id, 'actor_id' => $this->manager->id, 'action' => 'approved',
            'from_status' => 'COMPLETED', 'to_status' => 'APPROVED',
        ]);
    }

    public function test_manager_cannot_approve_a_task_outside_their_team(): void
    {
        $task = $this->taskFor($this->outsiderEmp, 'completed');

        $this->expectException(AuthorizationException::class);

        try {
            $this->workflow->approve($task, $this->manager);
        } finally {
            $this->assertSame(TaskStatus::Completed, $task->fresh()->status);
            $this->assertDatabaseCount('task_histories', 0);
        }
    }

    public function test_a_task_cannot_be_approved_twice(): void
    {
        $task = $this->taskFor($this->employeeEmp, 'completed');

        $this->workflow->approve($task, $this->manager);

        $this->expectException(InvalidTaskTransitionException::class);
        $this->workflow->approve($task, $this->manager);
    }

    public function test_double_approval_creates_only_one_history_record(): void
    {
        $task = $this->taskFor($this->employeeEmp, 'completed');

        $this->workflow->approve($task, $this->manager);

        try {
            $this->workflow->approve($task, $this->manager);
        } catch (InvalidTaskTransitionException) {
        }

        $this->assertSame(1, TaskHistory::where('task_id', $task->id)->where('action', 'approved')->count());
    }

    public function test_a_cancelled_task_cannot_be_approved(): void
    {
        $task = $this->taskFor($this->employeeEmp, 'cancelled');

        $this->expectException(InvalidTaskTransitionException::class);
        $this->workflow->approve($task, $this->manager);
    }

    public function test_a_task_that_is_not_completed_cannot_be_approved(): void
    {
        $task = $this->taskFor($this->employeeEmp, 'pending');

        $this->expectException(InvalidTaskTransitionException::class);
        $this->workflow->approve($task, $this->manager);
    }

    public function test_employee_cannot_approve_a_task(): void
    {
        $task = $this->taskFor($this->teammateEmp, 'completed');

        $this->expectException(AuthorizationException::class);
        $this->workflow->approve($task, $this->employee);
    }

    public function test_employee_cannot_approve_their_own_task(): void
    {
        $task = $this->taskFor($this->employeeEmp, 'completed');

        $this->expectException(AuthorizationException::class);
        $this->workflow->approve($task, $this->employee);
    }

    public function test_manager_cannot_approve_their_own_task(): void
    {
        $task = $this->taskFor($this->managerEmp, 'completed');

        $this->expectException(AuthorizationException::class);
        $this->workflow->approve($task, $this->manager);
    }

    public function test_hr_cannot_approve_tasks(): void
    {
        $task = $this->taskFor($this->employeeEmp, 'completed');

        $this->expectException(AuthorizationException::class);
        $this->workflow->approve($task, $this->hr);
    }

    public function test_rejection_requires_a_reason(): void
    {
        $task = $this->taskFor($this->employeeEmp, 'completed');

        Livewire::actingAs($this->manager)
            ->test(RejectTask::class, ['task' => $task])
            ->set('reason', '')
            ->call('reject')
            ->assertHasErrors(['reason' => 'required']);

        $this->assertSame(TaskStatus::Completed, $task->fresh()->status);
    }

    public function test_service_enforces_the_reason_even_if_the_form_is_bypassed(): void
    {
        $task = $this->taskFor($this->employeeEmp, 'completed');

        $this->expectException(ValidationException::class);
        $this->workflow->reject($task, $this->manager, '   ');
    }

    public function test_manager_can_reject_with_a_reason_and_it_is_recorded(): void
    {
        $task = $this->taskFor($this->employeeEmp, 'completed');

        Livewire::actingAs($this->manager)
            ->test(RejectTask::class, ['task' => $task])
            ->set('reason', 'Missing the supporting figures.')
            ->call('reject')
            ->assertHasNoErrors()
            ->assertRedirect(route('team.tasks'));

        $task->refresh();
        $this->assertSame(TaskStatus::Rejected, $task->status);
        $this->assertSame('Missing the supporting figures.', $task->rejection_reason);
        $this->assertDatabaseHas('task_histories', ['task_id' => $task->id, 'action' => 'rejected', 'reason' => 'Missing the supporting figures.']);
    }

    public function test_rejected_task_can_be_reworked_and_resubmitted(): void
    {
        $task = $this->taskFor($this->employeeEmp, 'completed');
        $this->workflow->reject($task, $this->manager, 'Needs more detail');

        $this->workflow->start($task, $this->employee);
        $this->assertSame(TaskStatus::InProgress, $task->fresh()->status);

        $this->workflow->complete($task, $this->employee);
        $this->workflow->approve($task, $this->manager);

        $this->assertSame(TaskStatus::Approved, $task->fresh()->status);
    }

    public function test_only_pending_tasks_can_be_cancelled(): void
    {
        $task = $this->taskFor($this->employeeEmp, 'inProgress');

        $this->expectException(InvalidTaskTransitionException::class);
        $this->workflow->cancel($task, $this->employee);
    }

    public function test_status_and_history_change_together_or_not_at_all(): void
    {
        $task = $this->taskFor($this->employeeEmp, 'completed');

        // Make the audit-record insert blow up AFTER the status update has been written.
        TaskHistory::creating(fn () => throw new \RuntimeException('disk full'));

        try {
            $this->workflow->approve($task, $this->manager);
            $this->fail('Expected the simulated failure.');
        } catch (\RuntimeException) {
        }

        // Transaction rolled back: still COMPLETED, nothing half-saved.
        $this->assertSame(TaskStatus::Completed, Task::find($task->id)->status);
        $this->assertNull(Task::find($task->id)->reviewed_by);
    }
}
