<?php

namespace Tests\Support\Factories\Domains\TransactionHistory\ValueObjects;

use App\Domains\TransactionHistory\ValueObjects\TransactionHistoryIdentifier;
use Tests\Support\Factories\Domains\Common\ValueObjects\UniversallyUniqueIdentifierFactory;

/**
 * テスト用の取引履歴識別子を生成するファクトリ.
 */
class TransactionHistoryIdentifierFactory extends UniversallyUniqueIdentifierFactory
{
    /**
     * {@inheritdoc}
     */
    protected function target(): string
    {
        return TransactionHistoryIdentifier::class;
    }
}
