<?php

namespace App\Validation\Rules\Schedule;

use App\Validation\Rules\AbstractRule;

/**
 * 繰り返し頻度バリデーションルール.
 */
class RepeatFrequency extends AbstractRule
{
    /**
     * 繰り返し頻度種別バリデーションルール.
     */
    private FrequencyType $typeRule;

    /**
     * コンストラクタ.
     */
    public function __construct()
    {
        $this->typeRule = new FrequencyType();
    }

    /**
     * {@inheritdoc}
     */
    protected function passes($attribute, $value): bool
    {
        if (\is_null($value)) {
            return true;
        }

        if (!\is_array($value)) {
            $this->message = ':attribute must be an array.';

            return false;
        }

        if (!$this->validateField($value, 'type') || !$this->validateField($value, 'interval')) {
            return false;
        }

        return true;
    }

    /**
     * 各フィールドのバリデーション.
     */
    protected function validateField(array $value, string $key): bool
    {
        if (!\array_key_exists($key, $value)) {
            $this->message = \sprintf(':attribute must have key `%s`.', $key);

            return false;
        }

        $field = $value[$key];

        if (\is_null($field)) {
            $this->message = \sprintf(':attribute.%s must not be null.', $key);

            return false;
        }

        if ($key === 'type') {
            $this->typeRule->validate('type', $field, function ($message) {
                $this->message = $message;
            });

            if (!\is_null($this->message)) {
                return false;
            }
        }

        if ($key === 'interval') {
            if (!\is_numeric($field)) {
                $this->message = \sprintf(':attribute.%s must be a number.', $key);

                return false;
            }

            if ($field < 1) {
                $this->message = \sprintf(':attribute.%s must be greater than 0.', $key);

                return false;
            }
        }

        return true;
    }
}
