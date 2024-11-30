<?php

namespace Tests\Support\Factories\Http\Encoders\Authentication;

use App\Http\Encoders\Authentication\TokenEncoder;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用のトークンエンコーダを生成するファクトリ.
 */
class TokenEncoderFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): TokenEncoder
    {
        return new TokenEncoder();
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): TokenEncoder
    {
        throw new \BadMethodCallException('Encoder cannot be duplicated.');
    }
}
