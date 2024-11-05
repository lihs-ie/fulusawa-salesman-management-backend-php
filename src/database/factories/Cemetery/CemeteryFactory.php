<?php

namespace Database\Factories\Cemetery;

use App\Domains\Cemetery\ValueObjects\CemeteryType;
use Database\Factories\Customer\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;

class CemeteryFactory extends Factory
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
            'name' => $this->faker->name(),
            'type' => Collection::make(CemeteryType::cases())
                ->random()
                ->name,
            'construction' => $this->faker->dateTime(),
            'in_house' => $this->faker->boolean(),
        ];
    }
}
