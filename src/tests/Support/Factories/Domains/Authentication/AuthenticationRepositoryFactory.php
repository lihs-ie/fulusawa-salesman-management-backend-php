<?php

namespace Tests\Support\Factories\Domains\Authentication;

use App\Domains\Authentication\AuthenticationRepository;
use App\Domains\Authentication\Entities\Authentication;
use App\Domains\Authentication\ValueObjects\AuthenticationIdentifier;
use App\Domains\Authentication\ValueObjects\Token;
use App\Domains\Authentication\ValueObjects\TokenType;
use App\Domains\User\ValueObjects\Role;
use App\Domains\User\ValueObjects\UserIdentifier;
use Carbon\CarbonImmutable;
use Illuminate\Support\Enumerable;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用の認証リポジトリを生成するファクトリ.
 *
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
class AuthenticationRepositoryFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): AuthenticationRepository
    {
        $instances = $overrides['instances'] ?? $builder->createList(Authentication::class, \abs($seed) % 10, $overrides);
        $onPersist = $overrides['onPersist'] ?? null;
        $onRemove = $overrides['onRemove'] ?? null;

        return new class($instances, $onPersist, $onRemove, $builder) implements AuthenticationRepository {
            private Enumerable $instances;

            public function __construct(
                Enumerable $instances,
                private readonly ?\Closure $onPersist,
                private readonly ?\Closure $onRemove,
                private readonly DependencyBuilder $builder
            ) {
                $this->instances = $instances->mapWithKeys(
                    fn (Authentication $token): array => [$token->identifier()->value() => $token]
                );
            }

            /**
             * {@inheritdoc}
             */
            public function persist(
                AuthenticationIdentifier $identifier,
                UserIdentifier $user,
                Role $role
            ): Authentication {
                $entity = $this->builder->create(Authentication::class, null, [
                    'identifier' => $identifier,
                ]);

                $existence = $this->instances->first(
                    fn (Authentication $instance): bool => $instance->identifier()->equals($identifier)
                );

                if (!\is_null($existence)) {
                    $access = $this->introspection($existence->accessToken()->value(), TokenType::ACCESS);
                    $refresh = $this->introspection($existence->refreshToken()->value(), TokenType::REFRESH);

                    if ($access && $refresh) {
                        return $existence;
                    }
                }

                $key = $identifier->value();

                $this->instances = clone $this->instances->put($key, $entity);

                if ($callback = $this->onPersist) {
                    $callback($entity);
                }

                return $entity;
            }

            /**
             * {@inheritdoc}
             */
            public function find(AuthenticationIdentifier $identifier): Authentication
            {
                $instance = $this->instances->first(
                    fn (Authentication $token): bool => $token->identifier()->equals($identifier)
                );

                if ($instance === null) {
                    throw new \OutOfBoundsException('Authentication not found.');
                }

                return $instance;
            }

            /**
             * {@inheritdoc}
             */
            public function introspection(string $value, TokenType $type): bool
            {
                $instance = $this->instances
                    ->first(function (Authentication $instance) use ($value, $type): bool {
                        return $type === TokenType::ACCESS ?
                            $instance->accessToken()->value() === $value :
                            $instance->refreshToken()->value() === $value;
                    })
                ;

                if (\is_null($instance)) {
                    return false;
                }

                $target = $type === TokenType::ACCESS ?
                    $instance->accessToken() :
                    $instance->refreshToken();

                return $target->expiresAt()->isFuture();
            }

            /**
             * {@inheritdoc}
             */
            public function refresh(string $value, TokenType $type): Authentication
            {
                if ($type !== TokenType::REFRESH) {
                    throw new \InvalidArgumentException('Token type is invalid.');
                }

                $instance = $this->instances->first(
                    fn (Authentication $instance): bool => $instance->refreshToken()->value() === $value
                );

                $next = $this->builder->create(Authentication::class, null, [
                    'identifier' => $instance->identifier(),
                    'accessToken' => $this->builder->create(Token::class, null, ['type' => TokenType::ACCESS, 'expiresAt' => CarbonImmutable::now()->addHours(\mt_rand(1, 24))]),
                    'refreshToken' => $this->builder->create(Token::class, null, ['type' => TokenType::REFRESH, 'expiresAt' => CarbonImmutable::now()->addHours(\mt_rand(1, 24))]),
                ]);

                $key = $next->identifier()->value();

                $this->instances = clone $this->instances->put($key, $next);

                return $next;
            }

            /**
             * {@inheritdoc}
             */
            public function revoke(string $value, TokenType $type): void
            {
                $target = $this->instances
                    ->when($type === TokenType::ACCESS, fn (Enumerable $collection): Enumerable => $collection->filter(
                        fn (Authentication $instance): bool => $instance->accessToken()->value() === $value
                    ))
                    ->when($type === TokenType::REFRESH, fn (Enumerable $collection): Enumerable => $collection->filter(
                        fn (Authentication $instance): bool => $instance->refreshToken()->value() === $value
                    ))
                    ->first()
                ;

                $revoked = $this->builder->create(Authentication::class, null, [
                    'identifier' => $target->identifier(),
                    'accessToken' => $type === TokenType::ACCESS ? null : $target->accessToken(),
                    'refreshToken' => $type === TokenType::REFRESH ? null : $target->refreshToken(),
                ]);

                $removed = $this->instances->put($target->identifier()->value(), $revoked);

                if ($callback = $this->onRemove) {
                    $callback($removed);
                }
            }

            /**
             * {@inheritdoc}
             */
            public function logout(AuthenticationIdentifier $identifier): void
            {
                $instance = $this->instances->first(
                    fn (Authentication $instance): bool => $instance->identifier()->equals($identifier)
                );

                if (\is_null($instance)) {
                    throw new \OutOfBoundsException('Authentication not found.');
                }

                $this->instances = $this->instances->reject(
                    fn (Authentication $instance): bool => $instance->identifier()->equals($identifier)
                );
            }
        };
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): AuthenticationRepository
    {
        throw new \BadMethodCallException('Repository can not be duplicated.');
    }
}
