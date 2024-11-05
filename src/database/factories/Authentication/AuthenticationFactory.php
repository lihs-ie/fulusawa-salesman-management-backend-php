<?php

namespace Database\Factories\Authentication;

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
            'access_token' => $this->faker->sha256(),
            'access_token_expires_at' => $this->faker->dateTime(),
            'refresh_token' => $this->faker->sha256(),
            'refresh_token_expires_at' => $this->faker->dateTime(),
            'abilities' => null,
            'last_used_at' => $this->faker->dateTime(),
        ];
    }

    public function bothValid(): static
    {
        return $this->state(fn (): array => [
            'access_token_expires_at' => CarbonImmutable::now()->addHour()->toAtomString(),
            'refresh_token_expires_at' => CarbonImmutable::now()->addDay()->toAtomString()
        ]);
    }

    public function bothExpired(): static
    {
        return $this->state(fn (): array => [
            'access_token_expires_at' => CarbonImmutable::now()->subHour()->toAtomString(),
            'refresh_token_expires_at' => CarbonImmutable::now()->subHour()->toAtomString()
        ]);
    }
}
