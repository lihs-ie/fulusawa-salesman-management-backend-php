<?php

namespace Tests\Support\Factories\Domains\Schedule\Entities;

use App\Domains\Common\ValueObjects\DateTimeRange;
use App\Domains\Schedule\Entities\Schedule;
use App\Domains\Schedule\ValueObjects\RepeatFrequency;
use App\Domains\Schedule\ValueObjects\ScheduleIdentifier;
use App\Domains\Schedule\ValueObjects\ScheduleStatus;
use App\Domains\User\ValueObjects\UserIdentifier;
use Illuminate\Support\Str;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用のスケジュールを生成するファクトリ.
 */
class ScheduleFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): Schedule
    {
        return new Schedule(
            identifier: $overrides['identifier'] ?? $builder->create(ScheduleIdentifier::class, $seed, $overrides),
            user: $overrides['user'] ?? $builder->create(UserIdentifier::class, $seed, $overrides),
            customer: $overrides['customer'] ?? null,
            title: $overrides['title'] ?? Str::random(\mt_rand(\abs($seed) % 10, 255)),
            description: $overrides['description'] ?? null,
            date: $overrides['date'] ?? $builder->create(DateTimeRange::class, $seed, ['filled' => true]),
            status: $overrides['status'] ?? $builder->create(ScheduleStatus::class, $seed, $overrides),
            repeat: $overrides['repeat'] ?? $builder->create(RepeatFrequency::class, $seed, $overrides),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): Schedule
    {
        if (!($instance instanceof Schedule)) {
            throw new \InvalidArgumentException('Invalid instance type.');
        }

        return new Schedule(
            identifier: $overrides['identifier'] ?? $instance->identifier(),
            user: $overrides['user'] ?? $instance->user(),
            customer: $overrides['customer'] ?? $instance->customer(),
            title: $overrides['title'] ?? $instance->title(),
            description: $overrides['description'] ?? $instance->description(),
            date: $overrides['date'] ?? $instance->date(),
            status: $overrides['status'] ?? $instance->status(),
            repeat: $overrides['repeat'] ?? $instance->repeat(),
        );
    }
}
