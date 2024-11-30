<?php

namespace App\Http\Encoders\TransactionHistory;

use App\Domains\TransactionHistory\Entities\TransactionHistory;

/**
 * 取引履歴エンコーダ.
 */
class TransactionHistoryEncoder
{
    public function encode(TransactionHistory $history): array
    {
        return [
          'identifier' => $history->identifier()->value(),
          'customer' => $history->customer()->value(),
          'user' => $history->user()->value(),
          'type' => $history->type()->name,
          'description' => $history->description(),
          'date' => $history->date()->toDateString(),
        ];
    }
}
