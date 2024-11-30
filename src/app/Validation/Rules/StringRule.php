<?php

namespace App\Validation\Rules;

/**
 * 文字列バリデーションの基底ルール.
 */
abstract class StringRule extends AbstractRule
{
    /**
     * エラーメッセージ.
     */
    protected ?string $message = null;

    /**
     * {@inheritdoc}
     */
    protected function passes($attribute, $value): bool
    {
        if (!\is_string($value)) {
            $this->message = ':attribute must be a string.';

            return false;
        }

        $length = \mb_strlen($value);

        if ($length === 0) {
            $this->message = ':attribute must not be empty.';

            return false;
        }

        $max = $this->maxLength();

        if (!\is_null($max) && $length > $max) {
            $this->message = \sprintf(
                ':attribute must have length lower than or equals to %d.',
                $max
            );

            return false;
        }

        return true;
    }

    /**
     * 最大文字数を取得する.
     */
    abstract protected function maxLength(): ?int;
}
