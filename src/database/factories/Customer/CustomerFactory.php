<?php

namespace Database\Factories\Customer;

use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
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
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone_number' => $phone,
            'address' => $address,
            'cemeteries' => json_encode([]),
            'transaction_histories' => json_encode([]),
        ];
    }
}
