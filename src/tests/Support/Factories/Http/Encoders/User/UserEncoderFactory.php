<?php

namespace Tests\Support\Factories\Http\Encoders\User;

use App\Http\Encoders\Common\AddressEncoder;
use App\Http\Encoders\Common\PhoneNumberEncoder;
use App\Http\Encoders\User\UserEncoder;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用のユーザーエンコーダを生成するファクトリ.
 */
class UserEncoderFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): UserEncoder
    {
        return new UserEncoder(
            addressEncoder: $builder->create(AddressEncoder::class),
            phoneEncoder: $builder->create(PhoneNumberEncoder::class),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): UserEncoder
    {
        throw new \BadMethodCallException('Encoder cannot be duplicated.');
    }
}
