<?php

namespace Tests\Support\Factories\Domains\Common\ValueObjects;

use App\Domains\Common\ValueObjects\PhoneNumber;
use Faker;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用の電話番号を生成するファクトリ.
 */
class PhoneNumberFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): PhoneNumber
    {
        $candidate = explode('-', Faker\Factory::create('ja_JP')->phoneNumber());

        return new PhoneNumber(
            areaCode: $overrides['areaCode'] ?? $candidate[0],
            localCode: $overrides['localCode'] ?? $candidate[1],
            subscriberNumber: $overrides['subscriberNumber'] ?? $candidate[2],
        );
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): PhoneNumber
    {
        if (!($instance instanceof PhoneNumber)) {
            throw new \InvalidArgumentException('Invalid instance type.');
        }

        return new PhoneNumber(
            areaCode: $overrides['areaCode'] ?? $instance->areaCode(),
            localCode: $overrides['localCode'] ?? $instance->localCode(),
            subscriberNumber: $overrides['subscriberNumber'] ?? $instance->subscriberNumber(),
        );
    }
}
