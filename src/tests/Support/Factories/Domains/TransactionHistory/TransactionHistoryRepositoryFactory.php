<?php

namespace Tests\Support\Factories\Domains\TransactionHistory;

use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\Domains\TransactionHistory\TransactionHistoryRepository;
use App\Domains\TransactionHistory\Entities\TransactionHistory;
use App\Domains\TransactionHistory\ValueObjects\TransactionHistoryIdentifier;
use App\Domains\User\ValueObjects\UserIdentifier;
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
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): TransactionHistoryRepository
    {
        $instances = $overrides['instances'] ?? $builder->createList(TransactionHistory::class, (\abs($seed) % 10), $overrides);
        $onPersist = $overrides['onPersist'] ?? null;
        $onRemove = $overrides['onRemove'] ?? null;

        return new class ($instances, $onPersist, $onRemove) implements TransactionHistoryRepository {
            private Enumerable $instances;

            public function __construct(
                Enumerable $instances,
                private readonly ?Closure $onPersist,
                private readonly ?Closure $onRemove
            ) {

                $this->instances = $instances->mapWithKeys(
                    fn (TransactionHistory $transactionHistory): array => [$transactionHistory->identifier()->value() => $transactionHistory]
                );
            }

            /**
             * {@inheritdoc}
             */
            public function persist(TransactionHistory $transactionHistory): void
            {
                $key = $transactionHistory->identifier()->value();

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
                $instance = $this->instances->first(
                    fn (TransactionHistory $transactionHistory): bool => $transactionHistory->identifier()->equals($identifier)
                );

                if ($instance === null) {
                    throw new \OutOfBoundsException('TransactionHistory not found.');
                }

                return $instance;
            }

            /**
             * {@inheritdoc}
             */
            public function list(): Enumerable
            {
                return $this->instances;
            }

            /**
             * {@inheritdoc}
             */
            public function ofUser(UserIdentifier $user): Enumerable
            {
                return $this->instances
                  ->filter(fn (TransactionHistory $transactionHistory): bool => $transactionHistory->user()->equals($user));
            }

            /**
             * {@inheritdoc}
             */
            public function ofCustomer(CustomerIdentifier $customer): Enumerable
            {
                return $this->instances
                  ->filter(fn (TransactionHistory $transactionHistory): bool => $transactionHistory->customer()->equals($customer));
            }

            /**
             * {@inheritdoc}
             */
            public function delete(TransactionHistoryIdentifier $identifier): void
            {
                $removed = $this->instances->reject(
                    fn (TransactionHistory $instance): bool => $instance->identifier()->equals($identifier)
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
