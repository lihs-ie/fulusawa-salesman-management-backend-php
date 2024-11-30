<?php

namespace App\Validation\Rules\TransactionHistory;

use App\Validation\Rules\EnumRule;

/**
 * 取引種別バリデーションルール.
 */
class TransactionType extends EnumRule
{
    /**
     * {@inheritdoc}
     */
    protected function source(): string
    {
        return \App\Domains\TransactionHistory\ValueObjects\TransactionType::class;
    }
}
