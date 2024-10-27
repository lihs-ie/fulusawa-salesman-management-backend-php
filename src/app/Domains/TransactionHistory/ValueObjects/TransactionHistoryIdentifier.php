<?php

namespace App\Domains\TransactionHistory\ValueObjects;

use App\Domains\Common\ValueObjects\UniversallyUniqueIdentifier;

/**
 * 取引履歴識別子を表す値オブジェクト
 */
class TransactionHistoryIdentifier extends UniversallyUniqueIdentifier
{
    public function __construct(string $value)
    {
        parent::__construct($value);
    }
}
