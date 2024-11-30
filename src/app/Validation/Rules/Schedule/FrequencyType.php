<?php

namespace App\Validation\Rules\Schedule;

use App\Validation\Rules\EnumRule;

/**
 * 繰り返し頻度種別バリデーションルール.
 */
class FrequencyType extends EnumRule
{
    /**
     * {@inheritdoc}
     */
    protected function source(): string
    {
        return \App\Domains\Schedule\ValueObjects\FrequencyType::class;
    }
}
