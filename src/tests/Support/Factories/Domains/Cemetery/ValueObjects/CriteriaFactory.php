<?php

namespace Tests\Support\Factories\Domains\Cemetery\ValueObjects;

use App\Domains\Cemetery\ValueObjects\Criteria;
use App\Domains\Customer\ValueObjects\CustomerIdentifier;
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
        return new Criteria(
            customer: $overrides['customer'] ?? $builder->create(CustomerIdentifier::class, $seed)
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
            customer: $instance->customer->duplicate()
        );
    }
}
