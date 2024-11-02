<?php

namespace App\UseCases;

use App\Domains\Cemetery\ValueObjects\CemeteryIdentifier;
use App\Domains\Customer\CustomerRepository;
use App\Domains\Customer\Entities\Customer as Entity;
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
    public function __construct(
        private readonly CustomerRepository $repository,
        private readonly CommonDomainFactory $factory
    ) {
    }

    /**
     * 顧客を永続化する
     *
     * @param string $identifier
     * @param array $name
     * @param array $address
     * @param array $phone
     * @param array $cemeteries
     * @param array $transactionHistories
     * @return void
     */
    public function persist(
        string $identifier,
        array $name,
        array $address,
        array $phone,
        array $cemeteries,
        array $transactionHistories,
    ): void {
        $entity = new Entity(
            identifier: new CustomerIdentifier($identifier),
            lastName: $this->factory->extractString($name, 'lastName'),
            firstName: $this->factory->extractString($name, 'firstName'),
            address: $this->factory->extractAddress($address),
            phone: $this->factory->extractPhone($phone),
            cemeteries: $this->extractCemeteries($cemeteries),
            transactionHistories: $this->extractTransactionHistories($transactionHistories),
        );

        $this->repository->persist($entity);
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
     * @return Enumerable
     */
    public function list(): Enumerable
    {
        return $this->repository->list();
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
}
