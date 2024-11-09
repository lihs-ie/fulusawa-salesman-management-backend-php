<?php

namespace Database\Factories\User;

use App\Domains\User\ValueObjects\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'identifier' => Uuid::uuid7()->toString(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone_area_code' => (string) '0' . \mt_rand(1, 999),
            'phone_local_code' => (string) \mt_rand(1, 9999),
            'phone_subscriber_number' => (string) \mt_rand(100, 99999),
            'role' => Collection::make(Role::cases())->random()->name,
            'postal_code_first' => (string) \mt_rand(100, 999),
            'postal_code_second' => (string) \mt_rand(1000, 9999),
            'prefecture' => \mt_rand(1, 47),
            'city' => fake()->city(),
            'street' => fake()->streetAddress(),
            'building' => fake()->optional()->secondaryAddress(),
            'email_verified_at' => now(),
            'password' => Hash::make('password' . \mt_rand(1, \PHP_INT_MAX)),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn () => [
            'email_verified_at' => null,
        ]);
    }

    public function roleOf(Role $role): static
    {
        return $this->state(fn () => [
            'role' => $role->name,
        ]);
    }
}
