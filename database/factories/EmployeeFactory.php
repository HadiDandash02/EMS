<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'department_id' => Department::factory(),
            'manager_id' => null,
            'employee_number' => 'EMP-'.fake()->unique()->numerify('#####'),
            'job_title' => fake()->jobTitle(),
            'hired_at' => fake()->dateTimeBetween('-5 years', '-1 month'),
        ];
    }

    public function withRole(UserRole $role): static
    {
        return $this->state(fn () => ['user_id' => User::factory()->role($role)]);
    }

    public function manager(): static
    {
        return $this->withRole(UserRole::Manager);
    }

    public function hr(): static
    {
        return $this->withRole(UserRole::Hr);
    }

    public function reportingTo(Employee $manager): static
    {
        return $this->state(fn () => [
            'manager_id' => $manager->id,
            'department_id' => $manager->department_id,
        ]);
    }
}
