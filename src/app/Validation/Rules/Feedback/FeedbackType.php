<?php

namespace App\Validation\Rules\Feedback;

use App\Validation\Rules\EnumRule;

/**
 * フィードバック種別バリデーションルール.
 */
class FeedbackType extends EnumRule
{
    /**
     * {@inheritdoc}
     */
    protected function source(): string
    {
        return \App\Domains\Feedback\ValueObjects\FeedbackType::class;
    }
}
