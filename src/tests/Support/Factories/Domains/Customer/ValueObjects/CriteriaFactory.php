<?php

namespace Tests\Support\Factories\Domains\Customer\ValueObjects;

use App\Domains\Common\ValueObjects\PhoneNumber;
use App\Domains\Common\ValueObjects\PostalCode;
use App\Domains\Customer\ValueObjects\Criteria;
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
            new Criteria(
                name: $overrides['name'] ?? Str::random(\mt_rand(1, Criteria::NAME_MAX_LENGTH)),
                postalCode: $overrides['postalCode'] ?? $builder->create(PostalCode::class, $seed, $overrides),
                phone: $overrides['phone'] ?? $builder->create(PhoneNumber::class, $seed, $overrides),
            );
        }

        return new Criteria(
            name: $overrides['name'] ?? null,
            postalCode: $overrides['postalCode'] ?? null,
            phone: $overrides['phone'] ?? null,
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
            name: $overrides['name'] ?? $instance->name(),
            postalCode: $overrides['postalCode'] ?? $instance->postalCode(),
            phone: $overrides['phone'] ?? $instance->phone(),
        );
    }
}
