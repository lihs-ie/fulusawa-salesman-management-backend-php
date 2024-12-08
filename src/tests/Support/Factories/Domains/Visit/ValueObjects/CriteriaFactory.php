<?php

namespace Tests\Support\Factories\Domains\Visit\ValueObjects;

use App\Domains\User\ValueObjects\UserIdentifier;
use App\Domains\Visit\ValueObjects\Criteria;
use App\Domains\Visit\ValueObjects\Criteria\Sort;
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
                user: $overrides['user'] ?? $builder->create(UserIdentifier::class, $seed, $overrides),
                sort: $overrides['sort'] ?? $builder->create(Sort::class, $seed, $overrides),
            );
        }

        return new Criteria(
            user: $overrides['user'] ?? null,
            sort: $overrides['sort'] ?? null,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): Criteria
    {
        if (!$instance instanceof Criteria) {
            throw new \InvalidArgumentException('Invalid instance type.');
        }

        return new Criteria(
            user: $overrides['user'] ?? $instance->user(),
            sort: $overrides['sort'] ?? $instance->sort(),
        );
    }
}
