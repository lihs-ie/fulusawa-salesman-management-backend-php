<?php

namespace Tests\Support\Factories\Domains\Cemetery;

use App\Domains\Cemetery\CemeteryRepository;
use App\Domains\Cemetery\Entities\Cemetery;
use App\Domains\Cemetery\ValueObjects\CemeteryIdentifier;
use App\Domains\Cemetery\ValueObjects\Criteria;
use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use Closure;
use Illuminate\Support\Enumerable;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用の墓地情報リポジトリを生成するファクトリ.
 */
class CemeteryRepositoryFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): CemeteryRepository
    {
        $instances = $overrides['instances'] ?? $builder->createList(Cemetery::class, (\abs($seed) % 10), $overrides);
        $onPersist = $overrides['onPersist'] ?? null;
        $onRemove = $overrides['onRemove'] ?? null;

        return new class ($instances, $onPersist, $onRemove) implements CemeteryRepository {
            private Enumerable $instances;

            public function __construct(
                Enumerable $instances,
                private readonly ?Closure $onPersist,
                private readonly ?Closure $onRemove
            ) {
                $this->instances = $instances->mapWithKeys(
                    fn (Cemetery $cemetery): array => [$this->keyOf($cemetery->identifier()) => $cemetery]
                );
            }

            /**
             * {@inheritdoc}
             */
            public function persist(Cemetery $cemetery): void
            {
                $key = $this->keyOf($cemetery->identifier());

                $this->instances = clone $this->instances->put($key, $cemetery);

                if ($callback = $this->onPersist) {
                    $callback($cemetery);
                }
            }

            /**
             * {@inheritdoc}
             */
            public function find(CemeteryIdentifier $identifier): Cemetery
            {
                $instance = $this->instances->first(
                    fn (Cemetery $cemetery): bool => $cemetery->identifier()->equals($identifier)
                );

                if ($instance === null) {
                    throw new \OutOfBoundsException('Cemetery not found.');
                }

                return $instance;
            }

            /**
             * {@inheritdoc}
             */
            public function list(Criteria $criteria): Enumerable
            {
                return clone $this->instances
                    ->when(!\is_null($criteria->customer), fn (Enumerable $collection) => $collection->filter(
                        fn (Cemetery $cemetery): bool => $criteria->customer()->equals($cemetery->customer)
                    ));
            }

            /**
             * {@inheritdoc}
             */
            public function delete(CemeteryIdentifier $identifier): void
            {
                $removed = $this->instances->reject(
                    fn (Cemetery $instance): bool => $instance->identifier()->equals($identifier)
                );

                if ($callback = $this->onRemove) {
                    $callback($removed);
                }
            }

            protected function keyOf(CemeteryIdentifier $identifier): string
            {
                return $identifier->value();
            }
        };
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): CemeteryRepository
    {
        throw new \BadMethodCallException('Repository can not be duplicated.');
    }
}
