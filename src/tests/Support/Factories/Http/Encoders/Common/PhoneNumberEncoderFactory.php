<?php

namespace Tests\Support\Factories\Http\Encoders\Common;

use App\Http\Encoders\Common\PhoneNumberEncoder;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用の電話番号エンコーダを生成するファクトリ.
 */
class PhoneNumberEncoderFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): PhoneNumberEncoder
    {
        return new PhoneNumberEncoder();
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): PhoneNumberEncoder
    {
        throw new \BadMethodCallException('Encoder cannot be duplicated.');
    }
}
