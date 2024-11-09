<?php

namespace App\Validation\Rules\User;

use App\Validation\Rules\EnumRule;

/**
 * ユーザー権限バリデーションルール.
 */
class Role extends EnumRule
{
    /**
     * {@inheritdoc}
     */
    protected function source(): string
    {
        return \App\Domains\User\ValueObjects\Role::class;
    }
}
