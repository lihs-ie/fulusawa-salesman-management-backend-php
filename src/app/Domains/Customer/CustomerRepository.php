<?php

namespace App\Domains\Customer;

use App\Domains\Customer\Entities\Customer;
use App\Domains\Customer\ValueObjects\Criteria;
use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use Illuminate\Support\Enumerable;

/**
 * 顧客リポジトリ
 */
interface CustomerRepository
{
    /**
     * 顧客を永続化する
     *
     * @param Customer $customer
     * @return void
     *
     * @throws ConflictException 顧客が既に存在する場合
     */
    public function add(Customer $customer): void;

    /**
     * 顧客を更新する
     *
     * @param Customer $customer
     * @return void
     *
     * @throws \OutOfBoundsException 顧客が存在しない場合
     * @throws ConflictException 顧客が既に存在する場合
     */
    public function update(Customer $customer): void;

    /**
     * 顧客を取得する
     *
     * @param CustomerIdentifier $identifier
     * @return Customer
     *
     * @throws \OutOfBoundsException 顧客が存在しない場合
     * @throws \UnexpectedValueException 顧客の各値が不正な場合
     */
    public function find(CustomerIdentifier $identifier): Customer;

    /**
     * 顧客一覧を取得する
     *
     * @param Criteria $criteria
     * @return Enumerable
     */
    public function list(Criteria $criteria): Enumerable;

    /**
     * 顧客を削除する
     *
     * @param CustomerIdentifier $identifier
     * @return void
     *
     * @throws \OutOfBoundsException 顧客が存在しない場合
     */
    public function delete(CustomerIdentifier $identifier): void;
}
