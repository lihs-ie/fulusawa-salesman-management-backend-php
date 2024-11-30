<?php

namespace App\Validation\Rules\Common;

use App\Validation\Rules\AssociativeArray;
use Illuminate\Validation\ValidationException;

/**
 * 住所バリデーションルール.
 */
class Address extends AssociativeArray
{
    /**
     * 都道府県バリデーションルール.
     */
    private Prefecture $prefectureRule;

    /**
     * 郵便番号バリデーションルール.
     */
    private PostalCode $postalCodeRule;

    /**
     * コンストラクタ.
     */
    public function __construct()
    {
        $this->prefectureRule = new Prefecture();
        $this->postalCodeRule = new PostalCode();
    }

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
            !$this->validateField($value, 'prefecture')
            || !$this->validateField($value, 'city')
            || !$this->validateField($value, 'street')
            || !$this->validateField($value, 'building')
            || !$this->validateField($value, 'postalCode')
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

        if ($key === 'building') {
            if (!\is_null($field) && !\is_string($field)) {
                $this->message = \sprintf(':attribute.%s must be a string.', $key);

                return false;
            }

            return true;
        }

        if ($key === 'city' || $key === 'street') {
            if (!\is_string($field)) {
                $this->message = \sprintf(':attribute.%s must be a string.', $key);

                return false;
            }

            return true;
        }

        if ($key === 'prefecture') {
            $this->prefectureRule->validate(
                $key,
                $field,
                fn (string $message) => throw ValidationException::withMessages([$key => [$message]])
            );
        }

        if ($key === 'postalCode') {
            $this->postalCodeRule->validate(
                $key,
                $field,
                fn (string $message) => throw ValidationException::withMessages([$key => [$message]])
            );
        }

        return true;
    }
}
