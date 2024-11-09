<?php

namespace App\Validation\Rules\Feedback;

use App\Validation\Rules\EnumRule;

/**
 * ソートバリデーションルール.
 */
class Sort extends EnumRule
{
    /**
     * {@inheritdoc}
     */
    protected function source(): string
    {
        return \App\Domains\Feedback\ValueObjects\Criteria\Sort::class;
    }
}
