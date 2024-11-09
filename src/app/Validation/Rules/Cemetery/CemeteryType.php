<?php

namespace App\Validation\Rules\Cemetery;

use App\Domains\Cemetery\ValueObjects;
use App\Validation\Rules\EnumRule;

/**
 * 墓地種別バリデーションルール.
 */
class CemeteryType extends EnumRule
{
    /**
     * {@inheritdoc}
     */
    protected function source(): string
    {
        return ValueObjects\CemeteryType::class;
    }
}
