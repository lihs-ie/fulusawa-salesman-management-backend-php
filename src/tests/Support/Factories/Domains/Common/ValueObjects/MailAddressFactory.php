<?php

namespace Tests\Support\Factories\Domains\Common\ValueObjects;

use App\Domains\Common\ValueObjects\MailAddress;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用のメールアドレスを生成するファクトリ.
 */
class MailAddressFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): MailAddress
    {
        return new MailAddress(
            value: $overrides['value'] ?? 'sample_' . $seed . '@example.com',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): MailAddress
    {
        if (!($instance instanceof MailAddress)) {
            throw new \InvalidArgumentException('Invalid instance type.');
        }

        return new MailAddress(
            value: $overrides['value'] ?? $instance->value(),
        );
    }
}
