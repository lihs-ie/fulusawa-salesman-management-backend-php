<?php

namespace Tests\Support\Factories\Domains\DailyReport\ValueObjects;

use App\Domains\Common\ValueObjects\DateTimeRange;
use App\Domains\DailyReport\ValueObjects\Criteria;
use App\Domains\User\ValueObjects\UserIdentifier;
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
        if (isset($overrides['fill']) && $overrides['fill']) {
            return new Criteria(
                date: $overrides['date'] ?? $builder->create(DateTimeRange::class, $seed, $overrides),
                user: $overrides['user'] ?? $builder->create(UserIdentifier::class, $seed, $overrides),
                isSubmitted: $overrides['isSubmitted'] ?? (bool) \mt_rand(0, 1),
            );
        }

        return new Criteria(
            date: $overrides['date'] ?? null,
            user: $overrides['user'] ?? null,
            isSubmitted: $overrides['isSubmitted'] ?? null,
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
            date: $overrides['date'] ?? $instance->date(),
            user: $overrides['user'] ?? $instance->user(),
            isSubmitted: $overrides['isSubmitted'] ?? $instance->isSubmitted(),
        );
    }
}
