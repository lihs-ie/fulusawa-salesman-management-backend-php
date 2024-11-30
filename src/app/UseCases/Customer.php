<?php

namespace App\UseCases;

use App\Domains\Cemetery\ValueObjects\CemeteryIdentifier;
use App\Domains\Customer\CustomerRepository;
use App\Domains\Customer\Entities\Customer as Entity;
use App\Domains\Customer\ValueObjects\Criteria;
use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\Domains\TransactionHistory\ValueObjects\TransactionHistoryIdentifier;
use App\UseCases\Factories\CommonDomainFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;

/**
 * 顧客ユースケース
 */
class Customer
{
    use CommonDomainFactory;

    /**
     * コンストラクタ.
     */
    public function __construct(
        private readonly CustomerRepository $repository,
    ) {
    }

    /**
     * 顧客を追加する.
     *
     * @param string $identifier
     * @param array $name
     * @param array $address
     * @param array $phone
     * @param array $cemeteries
     * @param array $transactionHistories
     * @return void
     */
    public function add(
        string $identifier,
        array $name,
        array $address,
        array $phone,
        array $cemeteries,
        array $transactionHistories,
    ): void {
        $entity = new Entity(
            identifier: new CustomerIdentifier($identifier),
            lastName: $this->extractString($name, 'last'),
            firstName: $this->extractString($name, 'first'),
            address: $this->extractAddress($address),
            phone: $this->extractPhone($phone),
            cemeteries: $this->extractCemeteries($cemeteries),
            transactionHistories: $this->extractTransactionHistories($transactionHistories),
        );

        $this->repository->add($entity);
    }

    /**
     * 顧客を更新する.
     *
     * @param string $identifier
     * @param array $name
     * @param array $address
     * @param array $phone
     * @param array $cemeteries
     * @param array $transactionHistories
     * @return void
     */
    public function update(
        string $identifier,
        array $name,
        array $address,
        array $phone,
        array $cemeteries,
        array $transactionHistories,
    ): void {
        $entity = new Entity(
            identifier: new CustomerIdentifier($identifier),
            lastName: $this->extractString($name, 'last'),
            firstName: $this->extractString($name, 'first'),
            address: $this->extractAddress($address),
            phone: $this->extractPhone($phone),
            cemeteries: $this->extractCemeteries($cemeteries),
            transactionHistories: $this->extractTransactionHistories($transactionHistories),
        );

        $this->repository->update($entity);
    }

    /**
     * 顧客を取得する
     *
     * @param string $identifier
     * @return Entity
     */
    public function find(string $identifier): Entity
    {
        return $this->repository->find(new CustomerIdentifier($identifier));
    }

    /**
     * 顧客一覧を取得する
     *
     * @param array $conditions
     * @return Enumerable
     */
    public function list(array $conditions): Enumerable
    {
        return $this->repository->list($this->createCriteria($conditions));
    }

    /**
     * 顧客を削除する
     *
     * @param string $identifier
     * @return void
     */
    public function delete(string $identifier): void
    {
        $this->repository->delete(new CustomerIdentifier($identifier));
    }

    /**
     * 配列から墓地識別子のリストを抽出する
     *
     * @param array $cemeteries
     * @return Enumerable
     */
    private function extractCemeteries(array $cemeteries): Enumerable
    {
        return Collection::make($cemeteries)
            ->map(fn (string $cemetery): CemeteryIdentifier => new CemeteryIdentifier($cemetery));
    }

    /**
     * 配列から取引履歴識別子のリストを抽出する
     *
     * @param array $transactions
     * @return Enumerable
     */
    private function extractTransactionHistories(array $transactions): Enumerable
    {
        return Collection::make($transactions)
            ->map(fn (string $transaction): TransactionHistoryIdentifier => new TransactionHistoryIdentifier(
                value: $transaction
            ));
    }

    /**
     * 配列から検索条件を生成する
     */
    private function createCriteria(array $conditions): Criteria
    {
        $postalCode =  $this->extractArray($conditions, 'postalCode');
        $phone = $this->extractArray($conditions, 'phone');

        return new Criteria(
            name: $this->extractString($conditions, 'name'),
            postalCode: $postalCode ? $this->extractPostalCode($postalCode) : null,
            phone: $phone ? $this->extractPhone($phone) : null,
        );
    }
}
