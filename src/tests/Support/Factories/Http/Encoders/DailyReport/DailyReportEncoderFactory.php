<?php

namespace Tests\Support\Factories\Http\Encoders\DailyReport;

use App\Http\Encoders\DailyReport\DailyReportEncoder;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用の日報エンコーダを生成するファクトリ.
 */
class DailyReportEncoderFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): DailyReportEncoder
    {
        return new DailyReportEncoder();
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): DailyReportEncoder
    {
        throw new \BadMethodCallException('Encoder cannot be duplicated.');
    }
}
