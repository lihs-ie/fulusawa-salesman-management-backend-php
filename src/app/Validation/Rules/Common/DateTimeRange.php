<?php

namespace App\Validation\Rules\Common;

use App\Validation\Rules\AbstractRule;
use Carbon\CarbonImmutable;

/**
 * 日時範囲バリデーションルール.
 */
class DateTimeRange extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    protected function passes($attribute, $value): bool
    {
        if (!\is_array($value)) {
            $this->message = ':attribute must be an array.';

            return false;
        }

        if (!$this->validateField($value, 'start') || !$this->validateField($value, 'end')) {
            return false;
        }

        if (!$this->validateBeforeAfter($value)) {
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

        if (!\is_null($field)) {
            if (!\is_string($field)) {
                $this->message = \sprintf(':attribute.%s must be a string.', $key);

                return false;
            }

            try {
                CarbonImmutable::createFromFormat(\DATE_ATOM, $field);
            } catch (\Exception $e) {
                $this->message = \sprintf(':attribute.%s must obey the format `%s`.', $key, \DATE_ATOM);

                return false;
            }
        }

        return true;
    }

    /**
     * 前後関係のバリデーション.
     */
    private function validateBeforeAfter(array $value): bool
    {
        $start = $value['start'];
        $end = $value['end'];

        if (\is_null($start) || \is_null($end)) {
            return true;
        }

        if (CarbonImmutable::parse($start)->gt(CarbonImmutable::parse($end))) {
            $this->message = ':attribute.start must be before :attribute.end.';

            return false;
        }

        return true;
    }
}
