<?php

namespace App\Validation\Rules\TransactionHistory;

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
        return \App\Domains\TransactionHistory\ValueObjects\Criteria\Sort::class;
    }
}
