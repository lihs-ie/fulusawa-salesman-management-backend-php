<?php

namespace Tests\Support\Factories\Http\Encoders\Common;

use App\Http\Encoders\Common\AddressEncoder;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用の住所エンコーダを生成するファクトリ.
 */
class AddressEncoderFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): AddressEncoder
    {
        return new AddressEncoder();
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): AddressEncoder
    {
        throw new \BadMethodCallException('Encoder cannot be duplicated.');
    }
}
