<?php

namespace Tests\Support\Factories\Domains\User;

use App\Domains\User\UserRepository;
use App\Domains\User\Entities\User;
use App\Domains\User\ValueObjects\UserIdentifier;
use Closure;
use Illuminate\Support\Enumerable;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用のユーザーリポジトリを生成するファクトリ.
 */
class UserRepositoryFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): UserRepository
    {
        $instances = $overrides['instances'] ?? $builder->createList(User::class, (\abs($seed) % 10), $overrides);
        $onPersist = $overrides['onPersist'] ?? null;
        $onRemove = $overrides['onRemove'] ?? null;

        return new class ($instances, $onPersist, $onRemove) implements UserRepository {
            private Enumerable $instances;

            public function __construct(
                Enumerable $instances,
                private readonly ?Closure $onPersist,
                private readonly ?Closure $onRemove
            ) {

                $this->instances = $instances->mapWithKeys(
                    fn (User $user): array => [$user->identifier()->value() => $user]
                );
            }

            /**
             * {@inheritdoc}
             */
            public function persist(User $user): void
            {
                $key = $user->identifier()->value();

                $this->instances = clone $this->instances->put($key, $user);

                if ($callback = $this->onPersist) {
                    $callback($user);
                }
            }

            /**
             * {@inheritdoc}
             */
            public function find(UserIdentifier $identifier): User
            {
                $instance = $this->instances->first(
                    fn (User $user): bool => $user->identifier()->equals($identifier)
                );

                if ($instance === null) {
                    throw new \OutOfBoundsException('User not found.');
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
            public function delete(UserIdentifier $identifier): void
            {
                $removed = $this->instances->reject(
                    fn (User $instance): bool => $instance->identifier()->equals($identifier)
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
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): UserRepository
    {
        throw new \BadMethodCallException('Repository can not be duplicated.');
    }
}
