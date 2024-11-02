<?php

namespace Tests\Support\Factories\Domains\Common\ValueObjects;

use App\Domains\Common\ValueObjects\Address;
use App\Domains\Common\ValueObjects\PostalCode;
use App\Domains\Common\ValueObjects\Prefecture;
use Illuminate\Support\Str;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用の都道府県を生成するファクトリ.
 */
class AddressFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): Address
    {
        return new Address(
            postalCode: $overrides['postalCode'] ?? $builder->create(PostalCode::class, $seed, $overrides),
            prefecture: $overrides['prefecture'] ?? $builder->create(Prefecture::class, $seed, $overrides),
            city: $overrides['city'] ?? Str::random(\mt_rand($seed % 10 + 1, 20)),
            street: $overrides['street'] ?? Str::random(\mt_rand($seed % 10 + 1, 20)),
            building: $overrides['building'] ?? Str::random(\mt_rand($seed % 10 + 1, 20)),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): Address
    {
        if (!($instance instanceof Address)) {
            throw new \InvalidArgumentException('Invalid instance type.');
        }

        return new Address(
            postalCode: $overrides['postalCode'] ?? $builder->duplicate($instance->postalCode(), $overrides),
            prefecture: $overrides['prefecture'] ?? $builder->duplicate($instance->prefecture(), $overrides),
            city: $overrides['city'] ?? $instance->city(),
            street: $overrides['street'] ?? $instance->street(),
            building: $overrides['building'] ?? $instance->building(),
        );
    }
}
