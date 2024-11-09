<?php

namespace Database\Factories\Feedback;

use App\Domains\Feedback\ValueObjects\FeedbackStatus;
use App\Domains\Feedback\ValueObjects\FeedbackType;
use DateInterval;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;

class FeedbackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $createdAt = $this->faker->dateTime()->format(\DATE_ATOM);

        return [
            'identifier' => Uuid::uuid7()->toString(),
            'type' => Collection::make(FeedbackType::cases())
                ->random()
                ->name,
            'status' => Collection::make(FeedbackStatus::cases())
                ->random()
                ->name,
            'content' => $this->faker->text(1000),
            'created_at' => $createdAt,
            'updated_at' => $createdAt
        ];
    }
}
