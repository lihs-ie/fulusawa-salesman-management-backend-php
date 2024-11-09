<?php

namespace App\UseCases;

use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\Domains\TransactionHistory\Entities\TransactionHistory as Entity;
use App\Domains\TransactionHistory\TransactionHistoryRepository;
use App\Domains\TransactionHistory\ValueObjects\TransactionHistoryIdentifier;
use App\Domains\TransactionHistory\ValueObjects\TransactionType;
use App\Domains\User\ValueObjects\UserIdentifier;
use App\UseCases\Factories\CommonDomainFactory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Enumerable;

/**
 * 取引履歴ユースケース
 */
class TransactionHistory
{
    use CommonDomainFactory;

    public function __construct(
        private readonly TransactionHistoryRepository $repository,
    ) {
    }

    /**
     * 取引履歴を永続化する
     *
     * @param string $identifier
     * @param string $customer
     * @param string $user
     * @param string $type
     * @param string|null $description
     * @param string $date
     * @return void
     */
    public function persist(
        string $identifier,
        string $customer,
        string $user,
        string $type,
        string|null $description,
        string $date
    ): void {
        $entity = new Entity(
            identifier: new TransactionHistoryIdentifier($identifier),
            customer: new CustomerIdentifier($customer),
            user: new UserIdentifier($user),
            type: $this->convertTransactionType($type),
            description: $description,
            date: CarbonImmutable::parse($date),
        );

        $this->repository->persist($entity);
    }

    /**
     * 取引履歴を取得する
     *
     * @param string $identifier
     * @return Entity
     */
    public function find(string $identifier): Entity
    {
        return $this->repository->find(new TransactionHistoryIdentifier($identifier));
    }

    /**
     * 取引履歴一覧を取得する
     *
     * @return Enumerable<Entity>
     */
    public function list(): Enumerable
    {
        return $this->repository->list();
    }

    /**
     * 取引履歴を削除する
     *
     * @param string $identifier
     * @return void
     */
    public function delete(string $identifier): void
    {
        $this->repository->delete(new TransactionHistoryIdentifier($identifier));
    }

    /**
     * ユーザー識別子を指定して取引履歴を取得する
     *
     * @param string $user
     * @return Enumerable
     */
    public function ofUser(string $user): Enumerable
    {
        return $this->repository->ofUser(new UserIdentifier($user));
    }

    /**
     * 顧客識別子を指定して取引履歴を取得する
     *
     * @param string $customer
     * @return Enumerable
     */
    public function ofCustomer(string $customer): Enumerable
    {
        return $this->repository->ofCustomer(new CustomerIdentifier($customer));
    }

    /**
     * 文字列から取引種別を生成する
     *
     * @param string $type
     * @return TransactionType
     */
    private function convertTransactionType(string $type): TransactionType
    {
        return match ($type) {
            TransactionType::MAINTENANCE->name => TransactionType::MAINTENANCE,
            TransactionType::CLEANING->name => TransactionType::CLEANING,
            TransactionType::GRAVESTONE_INSTALLATION->name => TransactionType::GRAVESTONE_INSTALLATION,
            TransactionType::GRAVESTONE_REMOVAL->name => TransactionType::GRAVESTONE_REMOVAL,
            TransactionType::GRAVESTONE_REPLACEMENT->name => TransactionType::GRAVESTONE_REPLACEMENT,
            TransactionType::GRAVESTONE_REPAIR->name => TransactionType::GRAVESTONE_REPAIR,
            TransactionType::OTHER->name => TransactionType::OTHER,
        };
    }
}
