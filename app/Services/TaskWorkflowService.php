<?php

namespace App\Services;

use App\Enums\TaskAction;
use App\Enums\TaskStatus;
use App\Exceptions\InvalidTaskTransitionException;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
    /**
     * @param  array{title:string,description?:?string,priority:string,due_date?:?string,employee_id?:int|string|null}  $data
     */
    public function create(User $actor, array $data): Task
    {
        // Employees/managers/HR always create for THEMSELVES; any employee_id they send is ignored.
        // Only the super admin may pick the owner.
        $ownerId = null;
        if ($actor->isSuperAdmin()) {
            Gate::forUser($actor)->authorize('createFor', Task::class);

            if (blank($data['employee_id'] ?? null)) {
                throw ValidationException::withMessages(['employee_id' => __('Choose the employee this task is for.')]);
            }
            $ownerId = (int) $data['employee_id'];
        } else {
            Gate::forUser($actor)->authorize('create', Task::class);
            $ownerId = $actor->employee->id;
        }
        unset($data['employee_id']);

        return DB::transaction(function () use ($actor, $data, $ownerId) {
            $task = new Task($data);                       // only whitelisted fields (mass assignment safe)
            $task->employee_id = $ownerId;
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
            // Owners may edit only PENDING tasks; the super admin may correct any task's details.
            if ($locked->status !== TaskStatus::Pending && ! $actor->isSuperAdmin()) {
                throw InvalidTaskTransitionException::notEditable($locked->status);
            }

            $locked->fill(Arr::except($data, ['employee_id']))->save();
            $this->log($locked, $actor, TaskAction::Updated, $locked->status, $locked->status);
        });
    }

    /**
     * Delete a task (its history goes with it).
     * Owners may delete only tasks nobody has worked on or reviewed yet (PENDING or CANCELLED);
     * the super admin may delete any task. Logged because it removes audit data.
     */
    public function delete(Task $task, User $actor): void
    {
        DB::transaction(function () use ($task, $actor) {
            $fresh = Task::query()->whereKey($task->getKey())->lockForUpdate()->firstOrFail();

            Gate::forUser($actor)->authorize('delete', $fresh);

            if (! $actor->isSuperAdmin() && ! in_array($fresh->status, [TaskStatus::Pending, TaskStatus::Cancelled], true)) {
                throw new InvalidTaskTransitionException(__('Only pending or cancelled tasks can be deleted (this task is :status).', ['status' => $fresh->status->label()]));
            }

            Log::info('Task deleted', ['task_id' => $fresh->id, 'title' => $fresh->title, 'status' => $fresh->status->value, 'by_user_id' => $actor->id]);
            $fresh->delete();
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
                throw new InvalidTaskTransitionException(__('Only pending tasks can be cancelled (this task is :status).', ['status' => $t->status->label()]));
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
                'reason' => __('A rejection reason of at least 5 characters is required.'),
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
