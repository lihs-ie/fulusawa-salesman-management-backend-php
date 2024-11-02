<?php

namespace Tests\Support\Factories\Domains\Common\ValueObjects;

use App\Domains\Common\ValueObjects\PostalCode;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用の郵便番号を生成するファクトリ.
 */
class PostalCodeFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): PostalCode
    {
        return new PostalCode(
            first: $overrides['first'] ?? str_pad((string) mt_rand($seed % 1000, 999), 3, '0', STR_PAD_LEFT),
            second: $overrides['second'] ?? str_pad((string) mt_rand($seed % 1000, 999), 4, '0', STR_PAD_LEFT),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): PostalCode
    {
        if (!($instance instanceof PostalCode)) {
            throw new \InvalidArgumentException('Invalid instance type.');
        }

        return new PostalCode(
            first: $overrides['first'] ?? $instance->first(),
            second: $overrides['second'] ?? $instance->second(),
        );
    }
}
