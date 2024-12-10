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
            ->name
        ;

        $phone = \json_encode([
            'areaCode' => (string) '0'.\mt_rand(1, 999),
            'localCode' => (string) \mt_rand(1, 9999),
            'subscriberNumber' => (string) \mt_rand(100, 99999),
        ]);

        $address = \json_encode([
            'postalCode' => [
                'first' => (string) \mt_rand(100, 999),
                'second' => (string) \mt_rand(1000, 9999),
            ],
            'prefecture' => \mt_rand(1, 47),
            'city' => fake()->city(),
            'street' => fake()->streetAddress(),
            'building' => fake()->optional()->secondaryAddress(),
        ]);

        return [
            'identifier' => Uuid::uuid7()->toString(),
            'user' => new UserFactory(),
            'visited_at' => $this->faker->dateTime(),
            'phone_number' => $phone,
            'address' => $address,
            'note' => fake()->optional()->sentence(),
            'has_graveyard' => (bool) \mt_rand(0, 1),
            'result' => $result,
        ];
    }
}
