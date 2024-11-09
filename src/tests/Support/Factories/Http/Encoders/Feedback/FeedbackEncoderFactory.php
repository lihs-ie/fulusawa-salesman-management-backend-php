<?php

namespace Tests\Support\Factories\Http\Encoders\Feedback;

use App\Http\Encoders\Feedback\FeedbackEncoder;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用のフィードバックエンコーダを生成するファクトリ.
 */
class FeedbackEncoderFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): FeedbackEncoder
    {
        return new FeedbackEncoder();
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): FeedbackEncoder
    {
        throw new \BadMethodCallException('Encoder cannot be duplicated.');
    }
}
