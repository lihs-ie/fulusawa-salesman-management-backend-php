<?php

namespace Tests\Support\Factories\Domains\Customer;

use App\Domains\Customer\CustomerRepository;
use App\Domains\Customer\Entities\Customer;
use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use Closure;
use Illuminate\Support\Enumerable;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用の顧客リポジトリを生成するファクトリ.
 */
class CustomerRepositoryFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): CustomerRepository
    {
        $instances = $overrides['instances'] ?? $builder->createList(Customer::class, (\abs($seed) % 10), $overrides);
        $onPersist = $overrides['onPersist'] ?? null;
        $onRemove = $overrides['onRemove'] ?? null;

        return new class ($instances, $onPersist, $onRemove) implements CustomerRepository {
            private Enumerable $instances;

            public function __construct(
                Enumerable $instances,
                private readonly ?Closure $onPersist,
                private readonly ?Closure $onRemove
            ) {

                $this->instances = $instances->mapWithKeys(
                    fn (Customer $customer): array => [$customer->identifier()->value() => $customer]
                );
            }

            /**
             * {@inheritdoc}
             */
            public function persist(Customer $customer): void
            {
                $key = $customer->identifier()->value();

                $this->instances = clone $this->instances->put($key, $customer);

                if ($callback = $this->onPersist) {
                    $callback($customer);
                }
            }

            /**
             * {@inheritdoc}
             */
            public function find(CustomerIdentifier $identifier): Customer
            {
                $instance = $this->instances->first(
                    fn (Customer $customer): bool => $customer->identifier()->equals($identifier)
                );

                if ($instance === null) {
                    throw new \OutOfBoundsException('Customer not found.');
                }

                return $instance;
            }

            /**
             * {@inheritdoc}
             */
            public function list(): Enumerable
            {
                return clone $this->instances;
            }

            /**
             * {@inheritdoc}
             */
            public function delete(CustomerIdentifier $identifier): void
            {
                $removed = $this->instances->reject(
                    fn (Customer $instance): bool => $instance->identifier()->equals($identifier)
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
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): CustomerRepository
    {
        throw new \BadMethodCallException('Repository can not be duplicated.');
    }
}
