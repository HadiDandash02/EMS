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
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
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
