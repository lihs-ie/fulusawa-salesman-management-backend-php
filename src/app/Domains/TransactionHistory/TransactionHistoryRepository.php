<?php

namespace App\Domains\TransactionHistory;

use App\Domains\TransactionHistory\Entities\TransactionHistory;
use App\Domains\TransactionHistory\ValueObjects\Criteria;
use App\Domains\TransactionHistory\ValueObjects\TransactionHistoryIdentifier;
use Illuminate\Support\Enumerable;

/**
 * 取引履歴リポジトリ
 */
interface TransactionHistoryRepository
{
    /**
     * 取引履歴を永続化する
     *
     * @param TransactionHistory $transactionHistory
     * @return void
     * 
     * @throws ConflictException 取引履歴が既に存在する場合
     */
    public function add(TransactionHistory $transactionHistory): void;

    /**
     * 取引履歴を更新する
     *
     * @param TransactionHistory $transactionHistory
     * @return void
     * 
     * @throws \OutOfBoundsException 取引履歴が存在しない場合
     */
    public function update(TransactionHistory $transactionHistory): void;

    /**
     * 取引履歴を取得する
     *
     * @param TransactionHistoryIdentifier $identifier
     * @return TransactionHistory
     * 
     * @throws \OutOfBoundsException 取引履歴が存在しない場合
     */
    public function find(TransactionHistoryIdentifier $identifier): TransactionHistory;

    /**
     * 取引履歴一覧を取得する
     *
     * @param Criteria $criteria
     * @return Enumerable
     */
    public function list(Criteria $criteria): Enumerable;

    /**
     * 取引履歴を削除する
     *
     * @param TransactionHistoryIdentifier $identifier
     * @return void
     * 
     * @throws \OutOfBoundsException 取引履歴が存在しない場合
     */
    public function delete(TransactionHistoryIdentifier $identifier): void;
}
