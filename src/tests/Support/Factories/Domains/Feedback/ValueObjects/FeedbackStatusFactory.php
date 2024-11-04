<?php

namespace Tests\Support\Factories\Domains\Feedback\ValueObjects;

use App\Domains\Feedback\ValueObjects\FeedbackStatus;
use Tests\Support\Factories\EnumFactory;

/**
 * テスト用のフィードバックステータスを生成するファクトリ.
 */
class FeedbackStatusFactory extends EnumFactory
{
    /**
     * {@inheritdoc}
     */
    protected function target(): string
    {
        return FeedbackStatus::class;
    }
}
