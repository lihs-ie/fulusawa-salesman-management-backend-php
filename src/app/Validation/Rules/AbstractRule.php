<?php

namespace App\Validation\Rules;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * バリデーションルールの基底クラス.
 */
abstract class AbstractRule implements ValidationRule
{
    /**
     * エラーメッセージ.
     */
    protected ?string $message = null;

    /**
     * {@inheritdoc}
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (!$this->passes($attribute, $value)) {
            $fail($this->message);
        }
    }

    /**
     * バリデーションの結果を返す.
     */
    abstract protected function passes($attribute, $value): bool;
}
