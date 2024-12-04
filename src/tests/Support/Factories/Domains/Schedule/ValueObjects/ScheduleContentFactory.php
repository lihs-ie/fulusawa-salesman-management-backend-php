<?php

namespace Tests\Support\Factories\Domains\Schedule\ValueObjects;

use App\Domains\Schedule\ValueObjects\ScheduleContent;
use Illuminate\Support\Str;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用のスケジュール内容を生成するファクトリ.
 */
class ScheduleContentFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): ScheduleContent
    {
        return new ScheduleContent(
            title: $overrides['title'] ?? Str::random(\mt_rand(1, 255)),
            description: $overrides['description'] ?? Str::random(\mt_rand(1, 255)),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): ScheduleContent
    {
        if (!($instance instanceof ScheduleContent)) {
            throw new \InvalidArgumentException('Invalid instance type.');
        }

        return new ScheduleContent(
            title: $overrides['title'] ?? $instance->title(),
            description: $overrides['description'] ?? $instance->description(),
        );
    }
}
