<?php

namespace Tests\Support\Factories\Domains\Feedback;

use App\Domains\Feedback\FeedbackRepository;
use App\Domains\Feedback\Entities\Feedback;
use App\Domains\Feedback\ValueObjects\Criteria;
use App\Domains\Feedback\ValueObjects\Criteria\Sort;
use App\Domains\Feedback\ValueObjects\FeedbackIdentifier;
use Closure;
use Illuminate\Support\Enumerable;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用のフィードバックリポジトリを生成するファクトリ.
 */
class FeedbackRepositoryFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): FeedbackRepository
    {
        $instances = $overrides['instances'] ?? $builder->createList(Feedback::class, (\abs($seed) % 10), $overrides);
        $onPersist = $overrides['onPersist'] ?? null;
        $onRemove = $overrides['onRemove'] ?? null;

        return new class ($instances, $onPersist, $onRemove) implements FeedbackRepository {
            private Enumerable $instances;

            public function __construct(
                Enumerable $instances,
                private readonly ?Closure $onPersist,
                private readonly ?Closure $onRemove
            ) {

                $this->instances = $instances->mapWithKeys(
                    fn (Feedback $feedback): array => [$feedback->identifier()->value() => $feedback]
                );
            }

            /**
             * {@inheritdoc}
             */
            public function persist(Feedback $feedback): void
            {
                $key = $feedback->identifier()->value();

                $this->instances = clone $this->instances->put($key, $feedback);

                if ($callback = $this->onPersist) {
                    $callback($feedback);
                }
            }

            /**
             * {@inheritdoc}
             */
            public function find(FeedbackIdentifier $identifier): Feedback
            {
                $instance = $this->instances->first(
                    fn (Feedback $feedback): bool => $feedback->identifier()->equals($identifier)
                );

                if ($instance === null) {
                    throw new \OutOfBoundsException('Feedback not found.');
                }

                return $instance;
            }

            /**
             * {@inheritdoc}
             */
            public function list(Criteria $criteria): Enumerable
            {
                $sort = fn (Enumerable $instances) => match ($criteria->sort()) {
                    Sort::CREATED_AT_ASC => $instances->sortBy(fn (Feedback $feedback): \DateTimeInterface => ($feedback->createdAt())),
                    Sort::CREATED_AT_DESC => $instances->sortByDesc(fn (Feedback $feedback): \DateTimeInterface => ($feedback->createdAt())),
                    Sort::UPDATED_AT_ASC => $instances->sortBy(fn (Feedback $feedback): \DateTimeInterface => ($feedback->updatedAt())),
                    Sort::UPDATED_AT_DESC => $instances->sortByDesc(fn (Feedback $feedback): \DateTimeInterface => ($feedback->updatedAt())),
                    null => $instances
                };

                $status = $criteria->status();
                $type = $criteria->type();

                return $this->instances
                  ->pipe($sort)
                  ->when(!\is_null($status), fn (Enumerable $instances): Enumerable => $instances->filter(
                      fn (Feedback $feedback): bool => $feedback->status() === $status
                  ))
                  ->when(!\is_null($type), fn (Enumerable $instances): Enumerable => $instances->filter(
                      fn (Feedback $feedback): bool => $feedback->type() === $type
                  ))
                  ->values();
            }

            /**
             * {@inheritdoc}
             */
            public function delete(FeedbackIdentifier $identifier): void
            {
                $removed = $this->instances->reject(
                    fn (Feedback $instance): bool => $instance->identifier()->equals($identifier)
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
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): FeedbackRepository
    {
        throw new \BadMethodCallException('Repository can not be duplicated.');
    }
}
