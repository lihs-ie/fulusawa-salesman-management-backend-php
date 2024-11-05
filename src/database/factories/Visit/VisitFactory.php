<?php

namespace Database\Factories\Visit;

use App\Domains\Visit\ValueObjects\VisitResult;
use Database\Factories\User\UserFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;

class VisitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $result = Collection::make(VisitResult::cases())
            ->random()
            ->name;

        $hasPhone = $result === VisitResult::CONTRACT->name;

        return [
            'identifier' => Uuid::uuid7()->toString(),
            'user' => new UserFactory(),
            'visited_at' => $this->faker->dateTime(),
            'phone_area_code' => $hasPhone ? (string) '0' . \mt_rand(1, 999) : null,
            'phone_local_code' => $hasPhone ? (string) \mt_rand(1, 9999) : null,
            'phone_subscriber_number' => $hasPhone ? (string) \mt_rand(100, 99999) : null,
            'postal_code_first' => (string) \mt_rand(100, 999),
            'postal_code_second' => (string) \mt_rand(1000, 9999),
            'prefecture' => \mt_rand(1, 47),
            'city' => fake()->city(),
            'street' => fake()->streetAddress(),
            'building' => fake()->optional()->secondaryAddress(),
            'note' => fake()->optional()->sentence(),
            'has_graveyard' => (bool)\mt_rand(0, 1),
            'result' => $result,
        ];
    }
}
