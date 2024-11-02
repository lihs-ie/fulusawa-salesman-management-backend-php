<?php

namespace Tests\Support\Factories\Domains\User\ValueObjects;

use App\Domains\User\ValueObjects\Role;
use Tests\Support\Factories\EnumFactory;

/**
 * テスト用のユーザー権限を生成するファクトリ.
 */
class RoleFactory extends EnumFactory
{
    /**
     * {@inheritdoc}
     */
    protected function target(): string
    {
        return Role::class;
    }
}
