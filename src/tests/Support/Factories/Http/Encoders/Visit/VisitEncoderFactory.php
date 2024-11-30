<?php

namespace Tests\Support\Factories\Http\Encoders\Visit;

use App\Http\Encoders\Common\AddressEncoder;
use App\Http\Encoders\Common\PhoneNumberEncoder;
use App\Http\Encoders\Visit\VisitEncoder;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用の訪問エンコーダを生成するファクトリ.
 */
class VisitEncoderFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): VisitEncoder
    {
        return new VisitEncoder(
            addressEncoder: $builder->create(AddressEncoder::class),
            phoneEncoder: $builder->create(PhoneNumberEncoder::class),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): VisitEncoder
    {
        throw new \BadMethodCallException('Encoder cannot be duplicated.');
    }
}
