<?php

namespace Tests\Support\Factories\Domains\TransactionHistory\ValueObjects;

use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\Domains\TransactionHistory\ValueObjects\Criteria;
use App\Domains\TransactionHistory\ValueObjects\Criteria\Sort;
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
    if (isset($overrides['fulfilled']) && $overrides['fulfilled']) {
      return new Criteria(
        user: $overrides['user'] ?? $builder->create(UserIdentifier::class),
        customer: $overrides['customer'] ?? $builder->create(CustomerIdentifier::class),
        sort: $overrides['sort'] ?? $builder->create(Sort::class),
      );
    }

    return new Criteria(
      user: $overrides['user'] ?? null,
      customer: $overrides['customer'] ?? null,
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
      user: $overrides['user'] ?? $instance->user(),
      customer: $overrides['customer'] ?? $instance->customer(),
      sort: $overrides['sort'] ?? $instance->sort(),
    );
  }
}
