<?php

namespace Tests\Support\Factories\Domains\Feedback\ValueObjects;

use App\Domains\Feedback\ValueObjects\Criteria;
use App\Domains\Feedback\ValueObjects\Criteria\Sort;
use App\Domains\Feedback\ValueObjects\FeedbackStatus;
use App\Domains\Feedback\ValueObjects\FeedbackType;
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
                status: $overrides['status'] ?? $builder->create(FeedbackStatus::class, $seed, $overrides),
                type: $overrides['type'] ?? $builder->create(FeedbackType::class, $seed, $overrides),
                sort: $overrides['sort'] ?? $builder->create(Sort::class, $seed, $overrides),
            );
        }


        return new Criteria(
            status: $overrides['status'] ?? null,
            type: $overrides['type'] ?? null,
            sort: $overrides['sort'] ?? null,
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
            type: $overrides['type'] ?? $instance->type(),
            sort: $overrides['sort'] ?? $instance->sort(),
        );
    }
}
