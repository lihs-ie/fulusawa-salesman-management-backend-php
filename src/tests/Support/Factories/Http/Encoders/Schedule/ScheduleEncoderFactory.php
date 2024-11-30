<?php

namespace Tests\Support\Factories\Http\Encoders\Schedule;

use App\Http\Encoders\Schedule\ScheduleEncoder;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用のスケジュールエンコーダを生成するファクトリ.
 */
class ScheduleEncoderFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): ScheduleEncoder
    {
        return new ScheduleEncoder();
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): ScheduleEncoder
    {
        throw new \BadMethodCallException('Encoder cannot be duplicated.');
    }
}
