<?php

namespace App\UseCases;

use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\Domains\TransactionHistory\Entities\TransactionHistory as Entity;
use App\Domains\TransactionHistory\TransactionHistoryRepository;
use App\Domains\TransactionHistory\ValueObjects\Criteria;
use App\Domains\TransactionHistory\ValueObjects\Criteria\Sort;
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

    /**
     * コンストラクタ.
     */
    public function __construct(
        private readonly TransactionHistoryRepository $repository,
    ) {}

    /**
     * 取引履歴を追加する
     *
     * @param string $identifier
     * @param string $customer
     * @param string $user
     * @param string $type
     * @param string|null $description
     * @param string $date
     * @return void
     */
    public function add(
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

        $this->repository->add($entity);
    }

    /**
     * 取引履歴を更新する
     *
     * @param string $identifier
     * @param string $customer
     * @param string $user
     * @param string $type
     * @param string|null $description
     * @param string $date
     * @return void
     */
    public function update(
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

        $this->repository->update($entity);
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
    public function list(array $conditions): Enumerable
    {
        return $this->repository->list($this->inflateCriteria($conditions));
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

    /**
     * 配列から検索条件を生成する
     *
     * @param array $conditions
     * @return Criteria
     */
    private function inflateCriteria(array $conditions): Criteria
    {
        $user = isset($conditions['user']) ?
            new UserIdentifier($this->extractString($conditions, 'user')) : null;

        $customer = isset($conditions['customer']) ?
            new CustomerIdentifier($this->extractString($conditions, 'customer')) : null;

        return new Criteria(
            user: $user,
            customer: $customer,
            sort: isset($conditions['sort']) ?
                $this->convertSort($this->extractString($conditions, 'sort')) : null,
        );
    }

    /**
     * 文字列からソート条件を生成する
     */
    private function convertSort(string $sort): Sort
    {
        return match ($sort) {
            Sort::CREATED_AT_ASC->name => Sort::CREATED_AT_ASC,
            Sort::CREATED_AT_DESC->name => Sort::CREATED_AT_DESC,
            Sort::UPDATED_AT_ASC->name => Sort::UPDATED_AT_ASC,
            Sort::UPDATED_AT_DESC->name => Sort::UPDATED_AT_DESC,
        };
    }
}
