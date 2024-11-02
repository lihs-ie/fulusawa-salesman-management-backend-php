<?php

namespace Tests\Support\Factories\Domains\Feedback\ValueObjects;

use App\Domains\Feedback\ValueObjects\FeedbackType;
use Tests\Support\Factories\EnumFactory;

/**
 * テスト用のフィードバック種別を生成するファクトリ.
 */
class FeedbackTypeFactory extends EnumFactory
{
    /**
     * {@inheritdoc}
     */
    protected function target(): string
    {
        return FeedbackType::class;
    }
}
