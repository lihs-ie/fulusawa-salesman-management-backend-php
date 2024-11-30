<?php

namespace App\Validation\Rules\Feedback;

use App\Validation\Rules\EnumRule;

/**
 * フィードバックステータスバリデーションルール.
 */
class FeedbackStatus extends EnumRule
{
    /**
     * {@inheritdoc}
     */
    protected function source(): string
    {
        return \App\Domains\Feedback\ValueObjects\FeedbackStatus::class;
    }
}
