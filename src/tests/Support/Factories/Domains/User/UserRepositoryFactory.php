<?php

namespace Tests\Support\Factories\Domains\User;

use App\Domains\User\Entities\User;
use App\Domains\User\UserRepository;
use App\Domains\User\ValueObjects\UserIdentifier;
use App\Exceptions\ConflictException;
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
     *
     * @suppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): UserRepository
    {
        $instances = $overrides['instances'] ?? $builder->createList(User::class, \abs($seed) % 10, $overrides);
        $onPersist = $overrides['onPersist'] ?? null;
        $onRemove = $overrides['onRemove'] ?? null;

        return new class($instances, $onPersist, $onRemove) implements UserRepository {
            private Enumerable $instances;

            public function __construct(
                Enumerable $instances,
                private readonly ?\Closure $onPersist,
                private readonly ?\Closure $onRemove
            ) {
                $this->instances = $instances->mapWithKeys(
                    fn (User $user): array => [$user->identifier()->value() => $user]
                );
            }

            /**
             * {@inheritdoc}
             */
            public function add(User $user): void
            {
                $key = $user->identifier()->value();

                if ($this->instances->has($key)) {
                    throw new ConflictException('User already exists.');
                }

                $duplicatedEmail = $this->instances->first(
                    fn (User $instance): bool => $user->email()->equals($instance->email())
                );

                if (!\is_null($duplicatedEmail) && !$duplicatedEmail->identifier()->equals($user->identifier())) {
                    throw new ConflictException(\sprintf('Email address "%s" is already in use.', $user->email()->value()));
                }

                $this->instances = clone $this->instances->put($key, $user);

                if ($callback = $this->onPersist) {
                    $callback($user);
                }
            }

            /**
             * {@inheritdoc}
             */
            public function update(User $user): void
            {
                $key = $user->identifier()->value();

                if (!$this->instances->has($key)) {
                    throw new \OutOfBoundsException('User not found.');
                }

                $duplicatedEmail = $this->instances->first(
                    fn (User $instance): bool => $user->email()->equals($instance->email())
                );

                if (!\is_null($duplicatedEmail) && !$duplicatedEmail->identifier()->equals($user->identifier())) {
                    throw new ConflictException(\sprintf('Email address "%s" is already in use.', $user->email()->value()));
                }

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
                if (!$this->instances->has($identifier->value())) {
                    throw new \OutOfBoundsException('User not found.');
                }

                return $this->instances->get($identifier->value());
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
                if (!$this->instances->has($identifier->value())) {
                    throw new \OutOfBoundsException('User not found.');
                }

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
