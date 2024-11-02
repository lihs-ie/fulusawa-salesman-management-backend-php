<?php

namespace Tests\Support\Factories\Domains\Schedule;

use App\Domains\Schedule\ScheduleRepository;
use App\Domains\Schedule\Entities\Schedule;
use App\Domains\Schedule\ValueObjects\Criteria;
use App\Domains\Schedule\ValueObjects\ScheduleIdentifier;
use App\Domains\User\ValueObjects\UserIdentifier;
use Closure;
use Illuminate\Support\Enumerable;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用のスケジュールリポジトリを生成するファクトリ.
 */
class ScheduleRepositoryFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): ScheduleRepository
    {
        $instances = $overrides['instances'] ?? $builder->createList(Schedule::class, (\abs($seed) % 10), $overrides);
        $onPersist = $overrides['onPersist'] ?? null;
        $onRemove = $overrides['onRemove'] ?? null;

        return new class ($instances, $onPersist, $onRemove) implements ScheduleRepository {
            private Enumerable $instances;

            public function __construct(
                Enumerable $instances,
                private readonly ?Closure $onPersist,
                private readonly ?Closure $onRemove
            ) {

                $this->instances = $instances->mapWithKeys(
                    fn (Schedule $schedule): array => [$schedule->identifier()->value() => $schedule]
                );
            }

            /**
             * {@inheritdoc}
             */
            public function persist(Schedule $schedule): void
            {
                $key = $schedule->identifier()->value();

                $this->instances = clone $this->instances->put($key, $schedule);

                if ($callback = $this->onPersist) {
                    $callback($schedule);
                }
            }

            /**
             * {@inheritdoc}
             */
            public function find(ScheduleIdentifier $identifier): Schedule
            {
                $instance = $this->instances->first(
                    fn (Schedule $schedule): bool => $schedule->identifier()->equals($identifier)
                );

                if ($instance === null) {
                    throw new \OutOfBoundsException('Schedule not found.');
                }

                return $instance;
            }

            /**
             * {@inheritdoc}
             */
            public function list(Criteria $criteria): Enumerable
            {
                $status = $criteria->status();
                $date = $criteria->date();
                $title = $criteria->title();

                return $this->instances
                  ->when(!\is_null($status), fn (Enumerable $instances) => $instances->filter(fn (Schedule $schedule): bool => $schedule->status() === $status))
                  ->when(!\is_null($date), function (Enumerable $instances) use ($date): Enumerable {
                      return $instances->filter(function (Schedule $schedule) use ($date): bool {
                          $candidate = $schedule->date();

                          return $candidate->includes($date->start()) && $candidate->includes($date->end());
                      });
                  })
                  ->when(!\is_null($title), function (Enumerable $instances) use ($title): Enumerable {
                      return $instances->filter(fn (Schedule $schedule): bool => str_contains($schedule->title(), $title));
                  });
            }

            /**
             * {@inheritdoc}
             */
            public function ofUser(UserIdentifier $user): Enumerable
            {
                return $this->instances
                  ->filter(fn (Schedule $schedule): bool => $schedule->user()->equals($user));
            }

            /**
             * {@inheritdoc}
             */
            public function delete(ScheduleIdentifier $identifier): void
            {
                $removed = $this->instances->reject(
                    fn (Schedule $instance): bool => $instance->identifier()->equals($identifier)
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
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): ScheduleRepository
    {
        throw new \BadMethodCallException('Repository can not be duplicated.');
    }
}
