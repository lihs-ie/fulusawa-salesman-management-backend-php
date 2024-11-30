<?php

namespace App\Validation\Rules;

/**
 * 整数バリデーションの基底ルール.
 */
abstract class IntegerRule extends AbstractRule
{
    /**
     * エラーメッセージ.
     */
    protected ?string $message = null;

    /**
     * {@inheritdoc}
     */
    public function passes($attribute, $value): bool
    {
        if (!\is_int($value) && !\is_numeric($value)) {
            $this->message = ':attribute must be a integer.';

            return false;
        }

        $value = (int) $value;

        $min = $this->min();
        $max = $this->max();

        if (!\is_null($min) && $value < $min) {
            $this->message = \sprintf(
                ':attribute must be larger than or equals to %d.',
                $min
            );

            return false;
        }

        if (!\is_null($max) && $value > $max) {
            $this->message = \sprintf(
                ':attribute must be smaller than or equals to %d.',
                $max
            );

            return false;
        }

        return true;
    }

    /**
     * 最小値を取得する.
     */
    protected function min(): ?int
    {
        return null;
    }

    /**
     * 最大値を取得する.
     */
    protected function max(): ?int
    {
        return null;
    }
}
