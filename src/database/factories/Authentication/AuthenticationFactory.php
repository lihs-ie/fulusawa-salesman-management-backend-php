<?php

namespace Database\Factories\Authentication;

use App\Domains\User\ValueObjects\Role;
use App\Infrastructures\User\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\User\UserFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

class AuthenticationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'identifier' => Uuid::uuid7()->toString(),
            'tokenable_id' => new UserFactory(),
            'tokenable_type' => User::class,
            'name' => $this->faker->name(),
            'token' => $this->faker->sha256(),
            'expires_at' => $this->faker->dateTime(),
            'refresh_token' => $this->faker->sha256(),
            'refresh_token_expires_at' => $this->faker->dateTime(),
            'abilities' => null,
            'last_used_at' => $this->faker->dateTime(),
        ];
    }

    public function bothValid(): static
    {
        return $this->state(fn (): array => [
            'expires_at' => CarbonImmutable::now()->addHour()->toAtomString(),
            'refresh_token_expires_at' => CarbonImmutable::now()->addDay()->toAtomString()
        ]);
    }

    public function bothExpired(): static
    {
        return $this->state(fn (): array => [
            'expires_at' => CarbonImmutable::now()->subHour()->toAtomString(),
            'refresh_token_expires_at' => CarbonImmutable::now()->subHour()->toAtomString()
        ]);
    }

    public function roleOf(Role $role = Role::ADMIN): static
    {
        $user = User::factory()->roleOf($role)->create();

        return $this->state(fn (): array => [
            'tokenable_id' => $user->identifier,
            'expires_at' => CarbonImmutable::now()->addDays(\mt_rand(1, 3))->toAtomString(),
            'refresh_token_expires_at' => CarbonImmutable::now()->addDays(\mt_rand(1, 3))->toAtomString(),
            'abilities' => $user->role
        ]);
    }
}
