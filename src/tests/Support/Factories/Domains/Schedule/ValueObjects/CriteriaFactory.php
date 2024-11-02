<?php

namespace Tests\Support\Factories\Domains\Schedule\ValueObjects;

use App\Domains\Common\ValueObjects\DateTimeRange;
use App\Domains\Schedule\ValueObjects\Criteria;
use App\Domains\Schedule\ValueObjects\ScheduleStatus;
use Illuminate\Support\Str;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用の検索条件を生成するファクトリ.
 */
class CriteriaFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): Criteria
    {

        if (isset($overrides['filled']) && $overrides['filled']) {
            return new Criteria(
                status: $overrides['status'] ?? $builder->create(ScheduleStatus::class, $seed, $overrides),
                date: $overrides['date'] ?? $builder->create(DateTimeRange::class, $seed, $overrides),
                title: $overrides['title'] ?? Str::random(\mt_rand(\abs($seed) % 10, 255)),
            );
        }

        return new Criteria(
            status: $overrides['status'] ?? null,
            date: $overrides['date'] ?? null,
            title: $overrides['title'] ?? null,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): Criteria
    {
        if (!($instance instanceof Criteria)) {
            throw new \InvalidArgumentException('Invalid instance type.');
        }

        return new Criteria(
            status: $overrides['status'] ?? $instance->status(),
            date: $overrides['date'] ?? $instance->date(),
            title: $overrides['title'] ?? $instance->title(),
        );
    }
}
