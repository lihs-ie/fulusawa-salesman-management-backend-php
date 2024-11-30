<?php

namespace Tests\Support\Factories\Http\Encoders\Customer;

use App\Http\Encoders\Common\AddressEncoder;
use App\Http\Encoders\Common\PhoneNumberEncoder;
use App\Http\Encoders\Customer\CustomerEncoder;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用の顧客エンコーダを生成するファクトリ.
 */
class CustomerEncoderFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): CustomerEncoder
    {
        return new CustomerEncoder(
            addressEncoder: $builder->create(AddressEncoder::class, $seed),
            phoneEncoder: $builder->create(PhoneNumberEncoder::class, $seed),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): CustomerEncoder
    {
        throw new \BadMethodCallException('Encoder cannot be duplicated.');
    }
}
