<?php

namespace Tests\Support\Factories\Domains\DailyReport\Entities;

use App\Domains\DailyReport\Entities\DailyReport;
use App\Domains\DailyReport\ValueObjects\DailyReportIdentifier;
use App\Domains\User\ValueObjects\UserIdentifier;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用の日報を生成するファクトリ.
 */
class DailyReportFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): DailyReport
    {
        return new DailyReport(
            identifier: $overrides['identifier'] ?? $builder->create(DailyReportIdentifier::class, $seed, $overrides),
            user: $overrides['user'] ?? $builder->create(UserIdentifier::class, $seed, $overrides),
            date: $overrides['date'] ?? CarbonImmutable::now()->subDays($seed % 10),
            schedules: $overrides['schedules'] ?? new Collection(),
            visits: $overrides['visits'] ?? new Collection(),
            isSubmitted: $overrides['isSubmitted'] ?? (bool) $seed % 2
        );
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): DailyReport
    {
        if (!($instance instanceof DailyReport)) {
            throw new \InvalidArgumentException('Invalid instance type.');
        }

        return new DailyReport(
            identifier: $overrides['identifier'] ?? $instance->identifier(),
            user: $overrides['user'] ?? $instance->user(),
            date: $overrides['date'] ?? $instance->date(),
            schedules: $overrides['schedules'] ?? $instance->schedules(),
            visits: $overrides['visits'] ?? $instance->visits(),
            isSubmitted: $overrides['isSubmitted'] ?? $instance->isSubmitted()
        );
    }
}
