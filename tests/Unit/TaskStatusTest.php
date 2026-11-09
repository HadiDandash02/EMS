<?php

namespace Tests\Unit;

use App\Enums\TaskStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Pure unit test: the status state machine needs no database or framework. */
class TaskStatusTest extends TestCase
{
    public static function allowed(): array
    {
        return [
            'pending -> in progress' => [TaskStatus::Pending, TaskStatus::InProgress],
            'pending -> cancelled' => [TaskStatus::Pending, TaskStatus::Cancelled],
            'in progress -> completed' => [TaskStatus::InProgress, TaskStatus::Completed],
            'completed -> approved' => [TaskStatus::Completed, TaskStatus::Approved],
            'completed -> rejected' => [TaskStatus::Completed, TaskStatus::Rejected],
            'rejected -> in progress (rework)' => [TaskStatus::Rejected, TaskStatus::InProgress],
        ];
    }

    public static function forbidden(): array
    {
        return [
            'approved -> pending' => [TaskStatus::Approved, TaskStatus::Pending],
            'approved -> rejected' => [TaskStatus::Approved, TaskStatus::Rejected],
            'approved -> approved' => [TaskStatus::Approved, TaskStatus::Approved],
            'cancelled -> pending' => [TaskStatus::Cancelled, TaskStatus::Pending],
            'cancelled -> approved' => [TaskStatus::Cancelled, TaskStatus::Approved],
            'pending -> approved (skips completion)' => [TaskStatus::Pending, TaskStatus::Approved],
            'pending -> completed (must be started first)' => [TaskStatus::Pending, TaskStatus::Completed],
            'in progress -> approved' => [TaskStatus::InProgress, TaskStatus::Approved],
            'in progress -> cancelled' => [TaskStatus::InProgress, TaskStatus::Cancelled],
            'rejected -> approved (needs rework first)' => [TaskStatus::Rejected, TaskStatus::Approved],
            'completed -> pending' => [TaskStatus::Completed, TaskStatus::Pending],
        ];
    }

    #[DataProvider('allowed')]
    public function test_allowed_transitions(TaskStatus $from, TaskStatus $to): void
    {
        $this->assertTrue($from->canTransitionTo($to));
    }

    #[DataProvider('forbidden')]
    public function test_forbidden_transitions(TaskStatus $from, TaskStatus $to): void
    {
        $this->assertFalse($from->canTransitionTo($to));
    }

    public function test_approved_and_cancelled_are_final(): void
    {
        $this->assertTrue(TaskStatus::Approved->isFinal());
        $this->assertTrue(TaskStatus::Cancelled->isFinal());
        $this->assertFalse(TaskStatus::Rejected->isFinal());
    }
}
