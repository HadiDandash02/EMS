<?php

namespace App\Services;

use App\Enums\TaskAction;
use App\Enums\TaskStatus;
use App\Exceptions\InvalidTaskTransitionException;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * The only place that changes a task's status. Controllers, Livewire components
 * and seeders all go through here, so the business rules cannot be bypassed.
 *
 * Every public method follows the same recipe inside ONE database transaction:
 *   1. re-read the task row with a pessimistic lock (lockForUpdate)
 *   2. authorise the actor against the fresh row (policy)
 *   3. verify the status transition is allowed
 *   4. update the task
 *   5. append a task_histories row
 *
 * Why a transaction? Steps 4 and 5 must succeed or fail together (no approved task
 * without an audit record). The row lock also stops two managers approving/rejecting
 * the same task at the same moment: the second one waits, re-reads the new status
 * and is refused by step 3.
 */
class TaskWorkflowService
{
    /** @param  array{title:string,description?:?string,priority:string,due_date?:?string}  $data */
    public function create(User $actor, array $data): Task
    {
        Gate::forUser($actor)->authorize('create', Task::class);

        return DB::transaction(function () use ($actor, $data) {
            $task = new Task($data);                       // only whitelisted fields (mass assignment safe)
            $task->employee_id = $actor->employee->id;     // ALWAYS the logged-in user, never from input
            $task->status = TaskStatus::Pending;
            $task->save();

            $this->log($task, $actor, TaskAction::Created, null, TaskStatus::Pending);

            return $task;
        });
    }

    /** @param  array{title:string,description?:?string,priority:string,due_date?:?string}  $data */
    public function update(Task $task, User $actor, array $data): Task
    {
        return $this->locked($task, $actor, 'update', function (Task $locked) use ($actor, $data) {
            if ($locked->status !== TaskStatus::Pending) {
                throw InvalidTaskTransitionException::notEditable($locked->status);
            }

            $locked->fill($data)->save();
            $this->log($locked, $actor, TaskAction::Updated, $locked->status, $locked->status);
        });
    }

    public function start(Task $task, User $actor): Task
    {
        return $this->locked($task, $actor, 'start', function (Task $t) use ($actor) {
            $action = $t->status === TaskStatus::Rejected ? TaskAction::Reopened : TaskAction::Started;
            $this->transition($t, $actor, TaskStatus::InProgress, $action);
        });
    }

    public function complete(Task $task, User $actor): Task
    {
        return $this->locked($task, $actor, 'complete', function (Task $t) use ($actor) {
            $t->completed_at = now();
            $this->transition($t, $actor, TaskStatus::Completed, TaskAction::Completed);
        });
    }

    public function cancel(Task $task, User $actor): Task
    {
        return $this->locked($task, $actor, 'cancel', function (Task $t) use ($actor) {
            // Business rule: only PENDING tasks may be cancelled.
            if ($t->status !== TaskStatus::Pending) {
                throw new InvalidTaskTransitionException("Only pending tasks can be cancelled (this task is {$t->status->label()}).");
            }
            $this->transition($t, $actor, TaskStatus::Cancelled, TaskAction::Cancelled);
        });
    }

    public function approve(Task $task, User $actor, ?string $note = null): Task
    {
        return $this->locked($task, $actor, 'approve', function (Task $t) use ($actor, $note) {
            $t->reviewed_by = $actor->id;
            $t->reviewed_at = now();
            $t->rejection_reason = null;
            $this->transition($t, $actor, TaskStatus::Approved, TaskAction::Approved, $note);
        });
    }

    public function reject(Task $task, User $actor, ?string $reason): Task
    {
        $reason = trim((string) $reason);

        // Business rule: a rejection MUST be explained. Enforced here too, not only in the form.
        if (mb_strlen($reason) < 5) {
            throw ValidationException::withMessages([
                'reason' => 'A rejection reason of at least 5 characters is required.',
            ]);
        }

        return $this->locked($task, $actor, 'reject', function (Task $t) use ($actor, $reason) {
            $t->reviewed_by = $actor->id;
            $t->reviewed_at = now();
            $t->rejection_reason = $reason;
            $this->transition($t, $actor, TaskStatus::Rejected, TaskAction::Rejected, $reason);
        });
    }

    /**
     * Lock -> authorise -> run the change -> return the refreshed task, all atomically.
     *
     * @param  callable(Task): void  $change
     */
    private function locked(Task $task, User $actor, string $ability, callable $change): Task
    {
        return DB::transaction(function () use ($task, $actor, $ability, $change) {
            $fresh = Task::query()->whereKey($task->getKey())->lockForUpdate()->firstOrFail();

            Gate::forUser($actor)->authorize($ability, $fresh);

            $change($fresh);

            return $fresh->refresh();
        });
    }

    private function transition(Task $task, User $actor, TaskStatus $to, TaskAction $action, ?string $reason = null): void
    {
        $from = $task->status;

        if (! $from->canTransitionTo($to)) {
            throw InvalidTaskTransitionException::between($from, $to);
        }

        $task->status = $to;
        $task->save();

        $this->log($task, $actor, $action, $from, $to, $reason);
    }

    private function log(Task $task, ?User $actor, TaskAction $action, ?TaskStatus $from, TaskStatus $to, ?string $reason = null): void
    {
        $task->histories()->create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'reason' => $reason,
        ]);
    }
}
