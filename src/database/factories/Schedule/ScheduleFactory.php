<?php

namespace Database\Factories\Schedule;

use App\Domains\Schedule\ValueObjects\ScheduleContent;
use App\Domains\Schedule\ValueObjects\ScheduleStatus;
use Database\Factories\User\UserFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class ScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = $this->faker->dateTime();

        return [
            'identifier' => Uuid::uuid7()->toString(),
            'participants' => UserFactory::new()
                ->count(\mt_rand(1, 10))
                ->create()
                ->pluck('identifier')
                ->toJson(),
            'creator' => new UserFactory(),
            'updater' => new UserFactory(),
            'customer' => null,
            'title' => Str::random(\mt_rand(1, ScheduleContent::MAX_TITLE_LENGTH)),
            'description' => Str::random(\mt_rand(5, ScheduleContent::MAX_DESCRIPTION_LENGTH)),
            'start' => $start,
            'end' => $this->faker->dateTimeBetween($start),
            'status' => Collection::make(ScheduleStatus::cases())->random()->name,
            'repeat' => null,
        ];
    }
}
