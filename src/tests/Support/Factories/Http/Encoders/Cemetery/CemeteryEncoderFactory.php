<?php

namespace Tests\Support\Factories\Http\Encoders\Cemetery;

use App\Http\Encoders\Cemetery\CemeteryEncoder;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用の墓地情報エンコーダを生成するファクトリ.
 */
class CemeteryEncoderFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): CemeteryEncoder
    {
        return new CemeteryEncoder();
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): CemeteryEncoder
    {
        throw new \BadMethodCallException('Encoder cannot be duplicated.');
    }
}
