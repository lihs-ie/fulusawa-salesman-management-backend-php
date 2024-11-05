<?php

namespace App\Infrastructures\TransactionHistory;

use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\Domains\TransactionHistory\Entities\TransactionHistory as Entity;
use App\Domains\TransactionHistory\TransactionHistoryRepository;
use App\Domains\TransactionHistory\ValueObjects\TransactionHistoryIdentifier;
use App\Domains\TransactionHistory\ValueObjects\TransactionType;
use App\Domains\User\ValueObjects\UserIdentifier;
use App\Infrastructures\TransactionHistory\Models\TransactionHistory as Record;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Enumerable;

/**
 * 取引履歴リポジトリ
 */
class EloquentTransactionHistoryRepository implements TransactionHistoryRepository
{
    /**
     * コンストラクタ.
     *
     * @param Record $builder
     */
    public function __construct(private readonly Record $builder)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function persist(Entity $transactionHistory): void
    {
        $this->createQuery()
          ->updateOrCreate(
              ['identifier' => $transactionHistory->identifier()->value()],
              [
              'identifier' => $transactionHistory->identifier()->value(),
              'customer' => $transactionHistory->customer()->value(),
              'user' => $transactionHistory->user()->value(),
              'type' => $transactionHistory->type()->name,
              'description' => $transactionHistory->description(),
              'date' => $transactionHistory->date()->toAtomString()
        ]
          );
    }

    /**
     * {@inheritDoc}
     */
    public function find(TransactionHistoryIdentifier $identifier): Entity
    {
        $record = $this->createQuery()
          ->where('identifier', $identifier->value())
          ->first();

        if (\is_null($record)) {
            throw new \OutOfBoundsException(\sprintf('TransactionHistory not found: %s', $identifier->value()));
        }

        return $this->restoreEntity($record);
    }

    /**
     * {@inheritDoc}
     */
    public function list(): Enumerable
    {
        return $this->createQuery()
          ->get()
          ->map(fn (Record $record): Entity => $this->restoreEntity($record));
    }

    /**
     * {@inheritDoc}
     */
    public function delete(TransactionHistoryIdentifier $identifier): void
    {
        $target = $this->createQuery()
          ->where('identifier', $identifier->value())
          ->first();

        if (\is_null($target)) {
            throw new \OutOfBoundsException(\sprintf('TransactionHistory not found: %s', $identifier->value()));
        }

        $target->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function ofUser(UserIdentifier $identifier): Enumerable
    {
        $records = $this->createQuery()
          ->where('user', $identifier->value())
          ->get();

        return $records->map(fn (Record $record): Entity => $this->restoreEntity($record));
    }

    /**
     * {@inheritDoc}
     */
    public function ofCustomer(CustomerIdentifier $identifier): Enumerable
    {
        $records = $this->createQuery()
          ->where('customer', $identifier->value())
          ->get();

        return $records->map(fn (Record $record): Entity => $this->restoreEntity($record));
    }

    /**
     * クエリビルダーを生成する.
     *
     * @return Builder
     */
    private function createQuery(): Builder
    {
        return $this->builder->newQuery();
    }

    /**
     * レコードからエンティティを復元する.
     *
     * @param Record $record
     * @return Entity
     */
    private function restoreEntity(Record $record): Entity
    {
        return new Entity(
            identifier: new TransactionHistoryIdentifier($record->identifier),
            customer: $record->customer ? new CustomerIdentifier($record->customer) : null,
            user: new UserIdentifier($record->user),
            type: $this->restoreType($record->type),
            description: $record->description,
            date: CarbonImmutable::parse($record->date),
        );
    }

    /**
     * 取引種別を復元する.
     *
     * @param string $status
     * @return TransactionType
     */
    private function restoreType(string $type): TransactionType
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
