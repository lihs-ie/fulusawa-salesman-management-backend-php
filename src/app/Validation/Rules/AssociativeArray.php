<?php

namespace App\Validation\Rules;

/**
 * 連想配列のバリデーションルール.
 */
abstract class AssociativeArray extends AbstractRule
{
    /**
     * 各フィールドのバリデーション.
     */
    abstract protected function validateField(array $value, string $key): bool;
}
