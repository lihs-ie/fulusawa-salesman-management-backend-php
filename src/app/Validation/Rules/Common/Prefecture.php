<?php

namespace App\Validation\Rules\Common;

use App\Domains\Common\ValueObjects\Prefecture as ValueObject;
use App\Validation\Rules\EnumRule;

/**
 * 都道府県バリデーションルール.
 */
class Prefecture extends EnumRule
{
    /**
     * {@inheritdoc}
     */
    protected function source(): string
    {
        return ValueObject::class;
    }
}
