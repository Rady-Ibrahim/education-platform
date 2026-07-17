<?php

namespace Database\Factories;

use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Academic\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('01#########'),
            'student_code' => null,
            'branch_id' => null,
            'created_by' => null,
            'status' => UserStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function pendingAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::PendingAdmin,
            'approved_at' => null,
            'approved_by' => null,
        ]);
    }

    public function forBranch(?Branch $branch = null): static
    {
        return $this->state(fn (array $attributes) => [
            'branch_id' => $branch?->id ?? Branch::defaultBranch()?->id,
        ]);
    }
}
