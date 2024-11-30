<?php

namespace App\Validation\Rules\User;

use App\Validation\Rules\StringRule;

/**
 * パスワードバリデーションルール.
 */
class Password extends StringRule
{
    /**
     * {@inheritdoc}
     */
    public function passes($attribute, $value): bool
    {
        if (!parent::passes($attribute, $value)) {
            return false;
        }

        if (!\preg_match(
            '/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/',
            $value
        )) {
            $this->message = ':attribute must be a valid password.';

            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function maxLength(): ?int
    {
        return 255;
    }
}
