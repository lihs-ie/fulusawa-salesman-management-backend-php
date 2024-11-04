<?php

namespace Tests\Support\Factories\Domains\Common\ValueObjects;

use App\Domains\Common\ValueObjects\DateTimeRange;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用の日時範囲を生成するファクトリ.
 */
class DateTimeRangeFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): DateTimeRange
    {
        if (isset($overrides['filled'])) {
            return new DateTimeRange(
                \now(),
                \now()->add('1 day')
            );
        }

        return new DateTimeRange(
            $overrides['start'] ?? null,
            $overrides['end'] ?? null
        );
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): DateTimeRange
    {
        if (!$instance instanceof DateTimeRange) {
            throw new \InvalidArgumentException('Invalid type of instance.');
        }

        return new DateTimeRange(
            array_key_exists('start', $overrides) ? $overrides['start'] : $instance->start(),
            array_key_exists('end', $overrides) ? $overrides['end'] : $instance->end()
        );
    }
}
