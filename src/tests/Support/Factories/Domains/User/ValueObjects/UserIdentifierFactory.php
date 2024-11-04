<?php

namespace Tests\Support\Factories\Domains\User\ValueObjects;

use App\Domains\User\ValueObjects\UserIdentifier;
use Tests\Support\Factories\Domains\Common\ValueObjects\UniversallyUniqueIdentifierFactory;

/**
 * テスト用のユーザー識別子を生成するファクトリ.
 */
class UserIdentifierFactory extends UniversallyUniqueIdentifierFactory
{
    /**
     * {@inheritdoc}
     */
    protected function target(): string
    {
        return UserIdentifier::class;
    }
}
