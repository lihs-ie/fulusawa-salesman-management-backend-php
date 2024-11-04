<?php

namespace Tests\Support\Factories\Domains\Authentication\ValueObjects;

use App\Domains\Authentication\ValueObjects\AuthenticationIdentifier;
use Tests\Support\Factories\Domains\Common\ValueObjects\UniversallyUniqueIdentifierFactory;

/**
 * テスト用の認証識別子を生成するファクトリ.
 */
class AuthenticationIdentifierFactory extends UniversallyUniqueIdentifierFactory
{
    /**
     * {@inheritdoc}
     */
    protected function target(): string
    {
        return AuthenticationIdentifier::class;
    }
}
