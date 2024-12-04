<?php

namespace App\Validation\Rules\Schedule;

use App\Validation\Rules\AbstractRule;

/**
 * スケジュール内容のバリデーションルール.
 */
class ScheduleContent extends AbstractRule
{
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

        if (!$this->validateTitle($value) || !$this->validateDescription($value)) {
            return false;
        }

        return true;
    }

    /**
     * titleのバリデーション.
     */
    private function validateTitle(array $value): bool
    {
        if (!\array_key_exists('title', $value)) {
            $this->message = \sprintf(':attribute must have key `%s`.', 'title');

            return false;
        }

        $field = $value['title'];

        if (\is_null($field)) {
            $this->message = \sprintf(':attribute.%s must not be null.', 'title');

            return false;
        }

        if (!\is_string($field)) {
            $this->message = \sprintf(':attribute.%s must be a string.', 'title');

            return false;
        }

        if ($field === '' || \App\Domains\Schedule\ValueObjects\ScheduleContent::MAX_TITLE_LENGTH < \mb_strlen($field)) {
            $this->message = \sprintf(
                ':attribute.%s must have length lower than or equals to %d.',
                'title',
                \App\Domains\Schedule\ValueObjects\ScheduleContent::MAX_TITLE_LENGTH
            );

            return false;
        }

        return true;
    }

    /**
     * descriptionのバリデーション.
     */
    private function validateDescription(array $value): bool
    {
        $field = $value['description'];

        if (\is_null($field)) {
            return true;
        }

        if (!\is_string($field)) {
            $this->message = \sprintf(':attribute.%s must be a string.', 'description');

            return false;
        }

        if ($field === '' || \App\Domains\Schedule\ValueObjects\ScheduleContent::MAX_DESCRIPTION_LENGTH < \mb_strlen($field)) {
            $this->message = \sprintf(
                ':attribute.%s must have length lower than or equals to %d.',
                'description',
                \App\Domains\Schedule\ValueObjects\ScheduleContent::MAX_DESCRIPTION_LENGTH
            );

            return false;
        }

        return true;
    }
}
