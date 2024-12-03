<?php

namespace Tests\Support\Factories\Domains\Schedule\Entities;

use App\Domains\Common\ValueObjects\DateTimeRange;
use App\Domains\Schedule\Entities\Schedule;
use App\Domains\Schedule\ValueObjects\RepeatFrequency;
use App\Domains\Schedule\ValueObjects\ScheduleContent;
use App\Domains\Schedule\ValueObjects\ScheduleIdentifier;
use App\Domains\Schedule\ValueObjects\ScheduleStatus;
use App\Domains\User\ValueObjects\UserIdentifier;
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
            participants: $overrides['participants'] ?? $builder->createList(UserIdentifier::class, \mt_rand(1, 10), $overrides),
            creator: $overrides['creator'] ?? $builder->create(UserIdentifier::class, $seed, $overrides),
            updater: $overrides['updater'] ?? $builder->create(UserIdentifier::class, $seed, $overrides),
            customer: $overrides['customer'] ?? null,
            content: $overrides['content'] ?? $builder->create(ScheduleContent::class, $seed, $overrides),
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
            participants: $overrides['participants'] ?? $instance->participants(),
            creator: $overrides['creator'] ?? $instance->creator(),
            updater: $overrides['updater'] ?? $instance->updater(),
            customer: $overrides['customer'] ?? $instance->customer(),
            content: $overrides['content'] ?? $instance->content(),
            date: $overrides['date'] ?? $instance->date(),
            status: $overrides['status'] ?? $instance->status(),
            repeat: $overrides['repeat'] ?? $instance->repeat(),
        );
    }
}
