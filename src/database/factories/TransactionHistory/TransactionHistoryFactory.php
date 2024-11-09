<?php

namespace Database\Factories\TransactionHistory;

use App\Domains\TransactionHistory\ValueObjects\TransactionType;
use Database\Factories\Customer\CustomerFactory;
use Database\Factories\User\UserFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;

class TransactionHistoryFactory extends Factory
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
            'customer' => new CustomerFactory(),
            'user' => new UserFactory(),
            'type' => Collection::make(TransactionType::cases())
                ->random()
                ->name,
            'description' => (bool)\mt_rand(0, 1) ? $this->faker->sentence() : null,
            'date' => $this->faker->date(),
        ];
    }
}
