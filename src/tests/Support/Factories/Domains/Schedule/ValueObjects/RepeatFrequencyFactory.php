<?php

namespace Tests\Support\Factories\Domains\Schedule\ValueObjects;

use App\Domains\Schedule\ValueObjects\FrequencyType;
use App\Domains\Schedule\ValueObjects\RepeatFrequency;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用の繰り返し頻度を生成するファクトリ.
 */
class RepeatFrequencyFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): RepeatFrequency
    {
        return new RepeatFrequency(
            type: $overrides['type'] ?? $builder->create(FrequencyType::class, $seed, $overrides),
            interval: $overrides['interval'] ?? \abs($seed % 10) + 1,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): RepeatFrequency
    {
        if (!($instance instanceof RepeatFrequency)) {
            throw new \InvalidArgumentException('Invalid instance type.');
        }

        return new RepeatFrequency(
            type: $overrides['type'] ?? $instance->type(),
            interval: $overrides['interval'] ?? $instance->interval(),
        );
    }
}
