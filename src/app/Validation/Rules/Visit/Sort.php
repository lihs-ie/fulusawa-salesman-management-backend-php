<?php

namespace App\Validation\Rules\Visit;

use App\Validation\Rules\EnumRule;

/**
 * ソート条件バリデーションルール.
 */
class Sort extends EnumRule
{
    /**
     * {@inheritdoc}
     */
    protected function source(): string
    {
        return \App\Domains\Visit\ValueObjects\Criteria\Sort::class;
    }
}
