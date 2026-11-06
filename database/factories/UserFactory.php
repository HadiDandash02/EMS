<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    // `role` is not mass assignable, so factories set it through forceFill-style attributes.
    public function role(UserRole $role): static
    {
        return $this->state(fn () => ['role' => $role]);
    }

    public function employee(): static
    {
        return $this->role(UserRole::Employee);
    }

    public function manager(): static
    {
        return $this->role(UserRole::Manager);
    }

    public function hr(): static
    {
        return $this->role(UserRole::Hr);
    }
}
