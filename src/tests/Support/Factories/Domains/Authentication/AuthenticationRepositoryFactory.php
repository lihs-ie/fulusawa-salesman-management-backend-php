<?php

namespace Tests\Support\Factories\Domains\Authentication;

use App\Domains\Authentication\AuthenticationRepository;
use App\Domains\Authentication\Entities\Authentication;
use App\Domains\Authentication\ValueObjects\AuthenticationIdentifier;
use App\Domains\Authentication\ValueObjects\Token;
use App\Domains\Common\ValueObjects\MailAddress;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;
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
            public function persist(AuthenticationIdentifier $identifier, MailAddress $mail, string $password): Authentication
            {
                $entity = $this->builder->create(Authentication::class, null, [
                    'identifier' => $identifier,
                ]);

                $existence = $this->instances->first(
                    fn (Authentication $instance): bool => $instance->identifier()->equals($identifier)
                );

                if (!\is_null($existence)) {
                    $validity = $this->introspection($existence->identifier());
                    $accessTokenValidity = $validity->get('accessToken')['active'];
                    $refreshTokenValidity = $validity->get('refreshToken')['active'];

                    if ($accessTokenValidity && $refreshTokenValidity) {
                        return $existence;
                    }

                    $this->revoke($identifier);
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
            public function introspection(Authentication $authentication): Enumerable
            {
                $result = new Collection([
                    'accessToken' => ['active' => true],
                    'refreshToken' => ['active' => true],
                ]);

                if (!$this->instances->has($authentication->identifier()->value())) {
                    throw new \OutOfBoundsException('Authentication not found.');
                }

                if ($authentication->accessToken()->expiresAt()->isPast()) {
                    $result->put('accessToken', ['active' => false]);
                }

                if ($authentication->refreshToken()->expiresAt()->isPast()) {
                    $result->put('refreshToken', ['active' => false]);
                }

                return $result;
            }

            /**
             * {@inheritdoc}
             */
            public function refresh(AuthenticationIdentifier $identifier): Authentication
            {
                $instance = $this->find($identifier);

                $next = $this->builder->create(Authentication::class, null, [
                    'identifier' => $instance->identifier(),
                    'accessToken' => $this->builder->create(Token::class, null, ['expiresAt' => CarbonImmutable::now()->addHours(\mt_rand(1, 24))]),
                    'refreshToken' => $this->builder->create(Token::class, null, ['expiresAt' => CarbonImmutable::now()->addHours(\mt_rand(1, 24))]),
                ]);

                $key = $next->identifier()->value();

                $this->instances = clone $this->instances->put($key, $next);

                return $next;
            }

            /**
             * {@inheritdoc}
             */
            public function revoke(AuthenticationIdentifier $identifier): void
            {
                $removed = $this->instances->reject(
                    fn (Authentication $instance): bool => $instance->identifier()->equals($identifier)
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
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): AuthenticationRepository
    {
        throw new \BadMethodCallException('Repository can not be duplicated.');
    }
}
