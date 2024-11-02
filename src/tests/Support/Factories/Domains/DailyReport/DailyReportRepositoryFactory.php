<?php

namespace Tests\Support\Factories\Domains\DailyReport;

use App\Domains\DailyReport\DailyReportRepository;
use App\Domains\DailyReport\Entities\DailyReport;
use App\Domains\DailyReport\ValueObjects\Criteria;
use App\Domains\DailyReport\ValueObjects\DailyReportIdentifier;
use Closure;
use Illuminate\Support\Enumerable;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用の日報リポジトリを生成するファクトリ.
 */
class DailyReportRepositoryFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): DailyReportRepository
    {
        $instances = $overrides['instances'] ?? $builder->createList(DailyReport::class, (\abs($seed) % 10), $overrides);
        $onPersist = $overrides['onPersist'] ?? null;
        $onRemove = $overrides['onRemove'] ?? null;

        return new class ($instances, $onPersist, $onRemove) implements DailyReportRepository {
            private Enumerable $instances;

            public function __construct(
                Enumerable $instances,
                private readonly ?Closure $onPersist,
                private readonly ?Closure $onRemove
            ) {

                $this->instances = $instances->mapWithKeys(
                    fn (DailyReport $dailyReport): array => [$dailyReport->identifier()->value() => $dailyReport]
                );
            }

            /**
             * {@inheritdoc}
             */
            public function persist(DailyReport $dailyReport): void
            {
                $key = $dailyReport->identifier()->value();

                $this->instances = clone $this->instances->put($key, $dailyReport);

                if ($callback = $this->onPersist) {
                    $callback($dailyReport);
                }
            }

            /**
             * {@inheritdoc}
             */
            public function find(DailyReportIdentifier $identifier): DailyReport
            {
                $instance = $this->instances->first(
                    fn (DailyReport $dailyReport): bool => $dailyReport->identifier()->equals($identifier)
                );

                if ($instance === null) {
                    throw new \OutOfBoundsException('DailyReport not found.');
                }

                return $instance;
            }

            /**
             * {@inheritdoc}
             */
            public function list(Criteria $criteria): Enumerable
            {
                $date = $criteria->date();
                $user = $criteria->user();
                $isSubmitted = $criteria->isSubmitted();

                return $this->instances
                  ->when(!\is_null($date), fn (Enumerable $instances) => $instances->filter(
                      fn (DailyReport $instance): bool => $date->includes($instance->date())
                  ))
                  ->when(!\is_null($user), fn (Enumerable $instances) => $instances->filter(
                      fn (DailyReport $instance): bool => $instance->user()->equals($user)
                  ))
                  ->when(!\is_null($isSubmitted), fn (Enumerable $instances) => $instances->filter(
                      fn (DailyReport $instance): bool => $instance->isSubmitted() === $isSubmitted
                  ));
            }

            /**
             * {@inheritdoc}
             */
            public function delete(DailyReportIdentifier $identifier): void
            {
                $removed = $this->instances->reject(
                    fn (DailyReport $instance): bool => $instance->identifier()->equals($identifier)
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
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): DailyReportRepository
    {
        throw new \BadMethodCallException('Repository can not be duplicated.');
    }
}
