<?php

namespace App\Validation\Rules\Common;

use App\Domains\Common\ValueObjects\PostalCode as ValueObject;
use App\Validation\Rules\AssociativeArray;

/**
 * 郵便番号バリデーションルール.
 */
class PostalCode extends AssociativeArray
{
    /**
     * {@inheritdoc}
     */
    protected function passes($attribute, $value): bool
    {
        if (!is_array($value)) {
            $this->message = ':attribute must be a numeric string.';

            return false;
        }

        if (!$this->validateField($value, 'first') || !$this->validateField($value, 'second')) {
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

        if ($key === 'first') {
            if (!\preg_match(ValueObject::VALID_FIRST_PATTERN, $field)) {
                $this->message = \sprintf(':attribute.%s must obey the format `%s`.', $key, ValueObject::VALID_FIRST_PATTERN);

                return false;
            }
        }

        if ($key === 'second') {
            if (!\preg_match(ValueObject::VALID_SECOND_PATTERN, $field)) {
                $this->message = \sprintf(':attribute.%s must obey the format `%s`.', $key, ValueObject::VALID_SECOND_PATTERN);

                return false;
            }
        }

        return true;
    }
}
