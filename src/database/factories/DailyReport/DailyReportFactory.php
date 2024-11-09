<?php

namespace Database\Factories\DailyReport;

use Database\Factories\User\UserFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

class DailyReportFactory extends Factory
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
            'user' => new UserFactory(),
            'date' => $this->faker->date,
            'schedules' => \json_encode([]),
            'visits' => \json_encode([]),
            'is_submitted' => $this->faker->boolean,
        ];
    }
}
