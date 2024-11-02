<?php

namespace Tests\Support\Factories\Domains\Visit;

use App\Domains\Visit\VisitRepository;
use App\Domains\Visit\Entities\Visit;
use App\Domains\Visit\ValueObjects\VisitIdentifier;
use Closure;
use Illuminate\Support\Enumerable;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用の訪問リポジトリを生成するファクトリ.
 */
class VisitRepositoryFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): VisitRepository
    {
        $instances = $overrides['instances'] ?? $builder->createList(Visit::class, (\abs($seed) % 10), $overrides);
        $onPersist = $overrides['onPersist'] ?? null;
        $onRemove = $overrides['onRemove'] ?? null;

        return new class ($instances, $onPersist, $onRemove) implements VisitRepository {
            private Enumerable $instances;

            public function __construct(
                Enumerable $instances,
                private readonly ?Closure $onPersist,
                private readonly ?Closure $onRemove
            ) {

                $this->instances = $instances->mapWithKeys(
                    fn (Visit $visit): array => [$visit->identifier()->value() => $visit]
                );
            }

            /**
             * {@inheritdoc}
             */
            public function persist(Visit $visit): void
            {
                $key = $visit->identifier()->value();

                $this->instances = clone $this->instances->put($key, $visit);

                if ($callback = $this->onPersist) {
                    $callback($visit);
                }
            }

            /**
             * {@inheritdoc}
             */
            public function find(VisitIdentifier $identifier): Visit
            {
                $instance = $this->instances->first(
                    fn (Visit $visit): bool => $visit->identifier()->equals($identifier)
                );

                if ($instance === null) {
                    throw new \OutOfBoundsException('Visit not found.');
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
            public function delete(VisitIdentifier $identifier): void
            {
                $removed = $this->instances->reject(
                    fn (Visit $instance): bool => $instance->identifier()->equals($identifier)
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
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): VisitRepository
    {
        throw new \BadMethodCallException('Repository can not be duplicated.');
    }
}
