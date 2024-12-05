<?php

namespace App\Infrastructures\TransactionHistory;

use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\Domains\TransactionHistory\Entities\TransactionHistory as Entity;
use App\Domains\TransactionHistory\TransactionHistoryRepository;
use App\Domains\TransactionHistory\ValueObjects\Criteria;
use App\Domains\TransactionHistory\ValueObjects\TransactionHistoryIdentifier;
use App\Domains\TransactionHistory\ValueObjects\TransactionType;
use App\Domains\User\ValueObjects\UserIdentifier;
use App\Infrastructures\Common\AbstractEloquentRepository;
use App\Infrastructures\TransactionHistory\Models\TransactionHistory as Record;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Enumerable;

/**
 * 取引履歴リポジトリ.
 */
class EloquentTransactionHistoryRepository extends AbstractEloquentRepository implements TransactionHistoryRepository
{
    /**
     * コンストラクタ.
     */
    public function __construct(private readonly Record $builder) {}

    /**
     * {@inheritDoc}
     */
    public function add(Entity $transactionHistory): void
    {
        try {
            $this->createQuery()
                ->create(
                    [
                        'identifier' => $transactionHistory->identifier()->value(),
                        'customer' => $transactionHistory->customer()->value(),
                        'user' => $transactionHistory->user()->value(),
                        'type' => $transactionHistory->type()->name,
                        'description' => $transactionHistory->description(),
                        'date' => $transactionHistory->date()->toAtomString(),
                    ]
                )
            ;
        } catch (\PDOException $exception) {
            $this->handlePDOException(
                exception: $exception,
                messages: $transactionHistory->identifier()->value()
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function update(Entity $transactionHistory): void
    {
        $target = $this->createQuery()
            ->ofIdentifier($transactionHistory->identifier())
            ->first()
        ;

        if (\is_null($target)) {
            throw new \OutOfBoundsException(\sprintf('TransactionHistory not found: %s', $transactionHistory->identifier()->value()));
        }

        try {
            $target->customer = $transactionHistory->customer()->value();
            $target->user = $transactionHistory->user()->value();
            $target->type = $transactionHistory->type()->name;
            $target->description = $transactionHistory->description();
            $target->date = $transactionHistory->date()->toAtomString();

            $target->save();
        } catch (\PDOException $exception) {
            $this->handlePDOException(
                exception: $exception,
                messages: $transactionHistory->identifier()->value()
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function find(TransactionHistoryIdentifier $identifier): Entity
    {
        $record = $this->createQuery()
            ->ofIdentifier($identifier)
            ->first()
        ;

        if (\is_null($record)) {
            throw new \OutOfBoundsException(\sprintf('TransactionHistory not found: %s', $identifier->value()));
        }

        return $this->restoreEntity($record);
    }

    /**
     * {@inheritDoc}
     */
    public function list(Criteria $criteria): Enumerable
    {
        return $this->createQuery()
            ->ofCriteria($criteria)
            ->get()
            ->map(fn (Record $record): Entity => $this->restoreEntity($record))
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(TransactionHistoryIdentifier $identifier): void
    {
        $target = $this->createQuery()
            ->ofIdentifier($identifier)
            ->first()
        ;

        if (\is_null($target)) {
            throw new \OutOfBoundsException(\sprintf('TransactionHistory not found: %s', $identifier->value()));
        }

        $target->delete();
    }

    /**
     * クエリビルダーを生成する.
     */
    private function createQuery(): Builder
    {
        return $this->builder->newQuery();
    }

    /**
     * レコードからエンティティを復元する.
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
