<?php

namespace Tests\Support\Factories\Domains\TransactionHistory\ValueObjects;

use App\Domains\TransactionHistory\ValueObjects\TransactionType;
use Tests\Support\Factories\EnumFactory;

/**
 * テスト用の取引履歴種別を生成するファクトリ.
 */
class TransactionTypeFactory extends EnumFactory
{
    /**
     * {@inheritdoc}
     */
    protected function target(): string
    {
        return TransactionType::class;
    }
}
