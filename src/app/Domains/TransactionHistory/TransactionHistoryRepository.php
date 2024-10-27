<?php

namespace App\Domains\TransactionHistory;

use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\Domains\TransactionHistory\Entities\TransactionHistory;
use App\Domains\TransactionHistory\ValueObjects\TransactionHistoryIdentifier;
use App\Domains\User\ValueObjects\UserIdentifier;
use Illuminate\Support\Enumerable;

/**
 * 取引履歴リポジトリ
 */
interface TransactionHistoryRepository
{
    public function persist(TransactionHistory $transactionHistory): void;

    public function find(TransactionHistoryIdentifier $identifier): TransactionHistory;

    public function list(): Enumerable;

    public function delete(TransactionHistoryIdentifier $identifier): void;

    public function ofUser(UserIdentifier $user): Enumerable;

    public function ofCustomer(CustomerIdentifier $customer): Enumerable;
}
