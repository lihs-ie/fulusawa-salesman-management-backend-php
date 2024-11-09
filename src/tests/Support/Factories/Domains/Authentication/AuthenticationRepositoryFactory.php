<?php

namespace Tests\Support\Factories\Domains\Authentication;

use App\Domains\Authentication\AuthenticationRepository;
use App\Domains\Authentication\Entities\Authentication;
use App\Domains\Authentication\ValueObjects\AuthenticationIdentifier;
use App\Domains\Authentication\ValueObjects\Token;
use App\Domains\Authentication\ValueObjects\TokenType;
use App\Domains\Common\ValueObjects\MailAddress;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Support\Collection;
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
        $instances = $overrides['instances'] ?? $builder->createList(Authentication::class, (\abs($seed) % 10), $overrides);
        $onPersist = $overrides['onPersist'] ?? null;
        $onRemove = $overrides['onRemove'] ?? null;

        return new class ($instances, $onPersist, $onRemove, $builder) implements AuthenticationRepository {
            private Enumerable $instances;

            public function __construct(
                Enumerable $instances,
                private readonly ?Closure $onPersist,
                private readonly ?Closure $onRemove,
                private readonly DependencyBuilder $builder
            ) {

                $this->instances = $instances->mapWithKeys(
                    fn (Authentication $token): array => [$token->identifier()->value() => $token]
                );
            }

            /**
             * {@inheritdoc}
             */
            public function persist(AuthenticationIdentifier $identifier, MailAddress $email, string $password): Authentication
            {
                $entity = $this->builder->create(Authentication::class, null, [
                    'identifier' => $identifier,
                ]);

                $existence = $this->instances->first(
                    fn (Authentication $instance): bool => $instance->identifier()->equals($identifier)
                );

                if (!\is_null($existence)) {
                    $access = $this->introspection($existence->access());
                    $refresh = $this->introspection($existence->refresh());

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
            public function introspection(Token $token): bool
            {
                $instance = $this->instances
                    ->first(function (Authentication $instance) use ($token): bool {
                        $type = $token->type();

                        return $type === TokenType::ACCESS ?
                            $instance->accessToken()->equals($token) :
                            $instance->refreshToken()->equals($token);
                    });

                if (\is_null($instance)) {
                    return false;
                }

                $target = $token->type() === TokenType::ACCESS ?
                    $instance->accessToken() :
                    $instance->refreshToken();

                return $target->expiresAt()->isFuture();
            }

            /**
             * {@inheritdoc}
             */
            public function refresh(Token $token): Authentication
            {
                if ($token->type() !== TokenType::REFRESH) {
                    throw new \InvalidArgumentException('Token type is invalid.');
                }

                $instance = $this->instances->first(
                    fn (Authentication $instance): bool => $instance->refreshToken()->equals($token)
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
            public function revoke(Token $token): void
            {
                $type = $token->type();

                $target = $this->instances
                    ->when($token->type() === TokenType::ACCESS, fn (Enumerable $collection): Enumerable => $collection->filter(
                        fn (Authentication $instance): bool => $instance->accessToken()->equals($token)
                    ))
                    ->when($token->type() === TokenType::REFRESH, fn (Enumerable $collection): Enumerable => $collection->filter(
                        fn (Authentication $instance): bool => $instance->refreshToken()->equals($token)
                    ))
                    ->first();

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
