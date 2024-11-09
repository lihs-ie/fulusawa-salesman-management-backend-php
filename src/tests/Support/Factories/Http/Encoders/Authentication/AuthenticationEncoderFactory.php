<?php

namespace Tests\Support\Factories\Http\Encoders\Authentication;

use App\Http\Encoders\Authentication\AuthenticationEncoder;
use App\Http\Encoders\Authentication\TokenEncoder;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用の認証エンコーダを生成するファクトリ.
 */
class AuthenticationEncoderFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): AuthenticationEncoder
    {
        return new AuthenticationEncoder(
            tokenEncoder: $builder->create(TokenEncoder::class, $seed, $overrides)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): AuthenticationEncoder
    {
        throw new \BadMethodCallException('Encoder cannot be duplicated.');
    }
}
