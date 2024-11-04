<?php

namespace Tests\Support\Factories\Domains\Feedback\Entities;

use App\Domains\Feedback\Entities\Feedback;
use App\Domains\Feedback\ValueObjects\FeedbackIdentifier;
use App\Domains\Feedback\ValueObjects\FeedbackStatus;
use App\Domains\Feedback\ValueObjects\FeedbackType;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用のフィードバックを生成するファクトリ.
 */
class FeedbackFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): Feedback
    {
        return new Feedback(
            identifier: $overrides['identifier'] ?? $builder->create(FeedbackIdentifier::class, $seed, $overrides),
            type: $overrides['type'] ?? $builder->create(FeedbackType::class, $seed, $overrides),
            status: $overrides['status'] ?? $builder->create(FeedbackStatus::class, $seed, $overrides),
            content: $overrides['content'] ?? Str::random(\mt_rand(\abs($seed % 10 + 1), 1000)),
            createdAt: $overrides['createdAt'] ?? CarbonImmutable::now()->subDays($seed % 10),
            updatedAt: $overrides['updatedAt'] ?? CarbonImmutable::now()->subDays($seed % 10),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): Feedback
    {
        if (!($instance instanceof Feedback)) {
            throw new \InvalidArgumentException('Invalid instance type.');
        }

        return new Feedback(
            identifier: $overrides['identifier'] ?? $builder->duplicate($instance->identifier(), $overrides),
            type: $overrides['type'] ?? $builder->duplicate($instance->type(), $overrides),
            status: $overrides['status'] ?? $builder->duplicate($instance->status(), $overrides),
            content: $overrides['content'] ?? $instance->content(),
            createdAt: $overrides['createdAt'] ?? $instance->createdAt(),
            updatedAt: $overrides['updatedAt'] ?? $instance->updatedAt(),
        );
    }
}
