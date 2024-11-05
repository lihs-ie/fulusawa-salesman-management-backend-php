<?php

namespace Tests\Support\Factories\Domains\Authentication\ValueObjects;

use App\Domains\Authentication\ValueObjects\TokenType;
use Tests\Support\Factories\EnumFactory;

/**
 * テスト用のトークン種別を生成するファクトリ.
 */
class TokenTypeFactory extends EnumFactory
{
    /**
     * {@inheritdoc}
     */
    protected function target(): string
    {
        return TokenType::class;
    }
}
