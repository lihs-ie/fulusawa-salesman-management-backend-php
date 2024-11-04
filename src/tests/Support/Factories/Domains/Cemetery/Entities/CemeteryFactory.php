<?php

namespace Tests\Support\Factories\Domains\Cemetery\Entities;

use App\Domains\Cemetery\Entities\Cemetery;
use App\Domains\Cemetery\ValueObjects\CemeteryIdentifier;
use App\Domains\Cemetery\ValueObjects\CemeteryType;
use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用の墓地情報を生成するファクトリ.
 */
class CemeteryFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): Cemetery
    {
        return new Cemetery(
            identifier: $overrides['identifier'] ?? $builder->create(CemeteryIdentifier::class, $seed, $overrides),
            customer: $overrides['customer'] ?? $builder->create(CustomerIdentifier::class, $seed, $overrides),
            name: $overrides['name'] ?? Str::random(\mt_rand(1, 255)),
            type: $overrides['type'] ?? $builder->create(CemeteryType::class, $seed, $overrides),
            construction: $overrides['construction'] ?? CarbonImmutable::now(),
            inHouse: $overrides['inHouse'] ?? (bool) $seed % 2
        );
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): Cemetery
    {
        if (!($instance instanceof Cemetery)) {
            throw new \InvalidArgumentException('Invalid instance type.');
        }

        return new Cemetery(
            identifier: $instance->identifier(),
            customer: $instance->customer(),
            name: $instance->name(),
            type: $instance->type(),
            construction: $instance->construction(),
            inHouse: $instance->inHouse()
        );
    }
}
