<?php

namespace App\Validation\Rules\Common;

use App\Domains\Common\ValueObjects\PhoneNumber as ValueObject;
use App\Validation\Rules\AssociativeArray;

/**
 * 電話番号バリデーションルール.
 */
class PhoneNumber extends AssociativeArray
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

        if (
            !$this->validateField($value, 'areaCode')
            || !$this->validateField($value, 'localCode')
            || !$this->validateField($value, 'subscriberNumber')
        ) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
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

        if (!\is_numeric($field)) {
            $this->message = \sprintf(':attribute.%s must be a numeric string.', $key);

            return false;
        }

        if ($key === 'areaCode') {
            if (!\preg_match(ValueObject::VALID_AREA_CODE_PATTERN, $field)) {
                $this->message = \sprintf(':attribute.%s must obey the format `%s`.', $key, ValueObject::VALID_AREA_CODE_PATTERN);

                return false;
            }
        } elseif ($key === 'localCode') {
            if (!\preg_match(ValueObject::VALID_LOCAL_CODE_PATTERN, $field)) {
                $this->message = \sprintf(':attribute.%s must obey the format `%s`.', $key, ValueObject::VALID_LOCAL_CODE_PATTERN);

                return false;
            }
        } elseif ($key === 'subscriberNumber') {
            if (!\preg_match(ValueObject::VALID_SUBSCRIBER_NUMBER_PATTERN, $field)) {
                $this->message = \sprintf(':attribute.%s must obey the format `%s`.', $key, ValueObject::VALID_SUBSCRIBER_NUMBER_PATTERN);

                return false;
            }
        } else {
            $this->message = \sprintf(':attribute.%s is invalid.', $key);

            return false;
        }

        return true;
    }
}
