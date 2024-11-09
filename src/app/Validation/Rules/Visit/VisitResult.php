<?php

namespace App\Validation\Rules\Visit;

use App\Validation\Rules\EnumRule;

/**
 * 訪問結果バリデーションルール.
 */
class VisitResult extends EnumRule
{
    /**
     * {@inheritdoc}
     */
    protected function source(): string
    {
        return \App\Domains\Visit\ValueObjects\VisitResult::class;
    }
}
