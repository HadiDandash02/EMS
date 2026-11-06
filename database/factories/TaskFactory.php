<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Employee;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /** Realistic English sample tasks (no Lorem Ipsum / foreign-language filler). */
    private const SAMPLES = [
        ['title' => 'Prepare the monthly sales report', 'description' => 'Collect the figures from every region, check them against the finance system and publish the summary.'],
        ['title' => 'Update the employee handbook', 'description' => 'Review the leave and remote-work policies and add the changes approved last quarter.'],
        ['title' => 'Fix the login page bug', 'description' => 'Users report an error when the session expires. Reproduce it, fix it and add a regression test.'],
        ['title' => 'Organise the team meeting', 'description' => 'Book a room, send the agenda and share the notes with the whole team afterwards.'],
        ['title' => 'Review the new vendor contract', 'description' => 'Check the pricing, delivery terms and cancellation clauses, then send comments to legal.'],
        ['title' => 'Design the onboarding checklist', 'description' => 'List everything a new hire needs in the first week: accounts, equipment and introductions.'],
        ['title' => 'Back up the customer database', 'description' => 'Run the weekly backup, verify that it restores correctly and store a copy offsite.'],
        ['title' => 'Write the project status update', 'description' => 'Summarise progress, risks and next steps for the management meeting on Thursday.'],
        ['title' => 'Test the mobile app release', 'description' => 'Go through the release checklist on Android and iOS and report any blocking issues.'],
        ['title' => 'Train the support team on the new tool', 'description' => 'Prepare a short workshop with practical examples and share the recording afterwards.'],
        ['title' => 'Reconcile the expense reports', 'description' => 'Match submitted receipts with the card statements and flag anything that is missing.'],
        ['title' => 'Plan the quarterly budget', 'description' => 'Collect requests from each department and prepare a draft budget for approval.'],
    ];

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            ...fake()->randomElement(self::SAMPLES),
            'priority' => fake()->randomElement(TaskPriority::cases()),
            'status' => TaskStatus::Pending,
            'due_date' => fake()->dateTimeBetween('now', '+30 days'),
        ];
    }

    public function status(TaskStatus $status): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'completed_at' => in_array($status, [TaskStatus::Completed, TaskStatus::Approved, TaskStatus::Rejected], true)
                ? now()
                : null,
        ]);
    }

    public function pending(): static
    {
        return $this->status(TaskStatus::Pending);
    }

    public function inProgress(): static
    {
        return $this->status(TaskStatus::InProgress);
    }

    public function completed(): static
    {
        return $this->status(TaskStatus::Completed);
    }

    public function approved(): static
    {
        return $this->status(TaskStatus::Approved);
    }

    public function cancelled(): static
    {
        return $this->status(TaskStatus::Cancelled);
    }

    public function forEmployee(Employee $employee): static
    {
        return $this->state(fn () => ['employee_id' => $employee->id]);
    }
}
