<?php

namespace Tests\Support\Factories\Domains\TransactionHistory;

use App\Domains\TransactionHistory\Entities\TransactionHistory;
use App\Domains\TransactionHistory\TransactionHistoryRepository;
use App\Domains\TransactionHistory\ValueObjects\Criteria;
use App\Domains\TransactionHistory\ValueObjects\Criteria\Sort;
use App\Domains\TransactionHistory\ValueObjects\TransactionHistoryIdentifier;
use App\Exceptions\ConflictException;
use Closure;
use Illuminate\Support\Enumerable;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用の取引履歴リポジトリを生成するファクトリ.
 */
class TransactionHistoryRepositoryFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     * 
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)   
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): TransactionHistoryRepository
    {
        $instances = $overrides['instances'] ?? $builder->createList(TransactionHistory::class, (\abs($seed) % 10), $overrides);
        $onPersist = $overrides['onPersist'] ?? null;
        $onRemove = $overrides['onRemove'] ?? null;

        return new class($instances, $onPersist, $onRemove) implements TransactionHistoryRepository {
            private Enumerable $instances;

            public function __construct(
                Enumerable $instances,
                private readonly ?Closure $onPersist,
                private readonly ?Closure $onRemove
            ) {

                $this->instances = $instances->mapWithKeys(
                    fn(TransactionHistory $transactionHistory): array => [$transactionHistory->identifier()->value() => $transactionHistory]
                );
            }

            /**
             * {@inheritdoc}
             */
            public function add(TransactionHistory $transactionHistory): void
            {
                $key = $transactionHistory->identifier()->value();

                if ($this->instances->has($key)) {
                    throw new ConflictException('TransactionHistory already exists.');
                }

                $this->instances = clone $this->instances->put($key, $transactionHistory);

                if ($callback = $this->onPersist) {
                    $callback($transactionHistory);
                }
            }

            /**
             * {@inheritdoc}
             */
            public function update(TransactionHistory $transactionHistory): void
            {
                $key = $transactionHistory->identifier()->value();

                if (!$this->instances->has($key)) {
                    throw new \OutOfBoundsException('TransactionHistory not found.');
                }

                $this->instances = clone $this->instances->put($key, $transactionHistory);

                if ($callback = $this->onPersist) {
                    $callback($transactionHistory);
                }
            }

            /**
             * {@inheritdoc}
             */
            public function find(TransactionHistoryIdentifier $identifier): TransactionHistory
            {
                if (!$this->instances->has($identifier->value())) {
                    throw new \OutOfBoundsException('TransactionHistory not found.');
                }

                $instance = $this->instances->get($identifier->value());

                return $instance;
            }

            /**
             * {@inheritdoc}
             */
            public function list(Criteria $criteria): Enumerable
            {
                return $this->instances
                    ->when(!\is_null($criteria->user()), fn(Enumerable $instances) => $instances->filter(
                        fn(TransactionHistory $transactionHistory): bool => $criteria->user()->equals($transactionHistory->user())
                    ))
                    ->when(!\is_null($criteria->customer()), fn(Enumerable $instances) => $instances->filter(
                        fn(TransactionHistory $transactionHistory): bool => $criteria->customer()->equals($transactionHistory->customer())
                    ))
                    ->when(!\is_null($criteria->sort()), fn(Enumerable $instances) => match ($criteria->sort()) {
                        Sort::CREATED_AT_ASC => $instances->sortBy(fn(TransactionHistory $instance): \DateTimeInterface => $instance->date()),
                        Sort::CREATED_AT_DESC => $instances->sortByDesc(fn(TransactionHistory $instance): \DateTimeInterface => $instance->date()),
                        Sort::UPDATED_AT_ASC => $instances->sortBy(fn(TransactionHistory $instance): \DateTimeInterface => $instance->date()),
                        Sort::UPDATED_AT_DESC => $instances->sortByDesc(fn(TransactionHistory $instance): \DateTimeInterface => $instance->date()),
                    })
                    ->values();
            }

            /**
             * {@inheritdoc}
             */
            public function delete(TransactionHistoryIdentifier $identifier): void
            {
                if (!$this->instances->has($identifier->value())) {
                    throw new \OutOfBoundsException('TransactionHistory not found.');
                }

                $removed = $this->instances->reject(
                    fn(TransactionHistory $instance): bool => $instance->identifier()->equals($identifier)
                );

                if ($callback = $this->onRemove) {
                    $callback($removed);
                }
            }
        };
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): TransactionHistoryRepository
    {
        throw new \BadMethodCallException('Repository can not be duplicated.');
    }
}
