<?php

namespace App\Infrastructures\Authentication;

use App\Domains\Authentication\AuthenticationRepository;
use App\Domains\Authentication\Entities\Authentication as Entity;
use App\Domains\Authentication\ValueObjects\AuthenticationIdentifier;
use App\Domains\Authentication\ValueObjects\Token;
use App\Domains\Authentication\ValueObjects\TokenType;
use App\Domains\User\ValueObjects\Role;
use App\Domains\User\ValueObjects\UserIdentifier;
use App\Exceptions\InvalidTokenException;
use App\Infrastructures\Authentication\Models\Authentication as Record;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * 認証リポジトリ.
 */
class EloquentAuthenticationRepository implements AuthenticationRepository
{
    /**
     * コンストラクタ.
     */
    public function __construct(
        private readonly Record $builder,
        private readonly int $accessTokenTTL,
        private readonly int $refreshTokenTTL,
        private readonly string $tokenableType,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function persist(
        AuthenticationIdentifier $identifier,
        UserIdentifier $user,
        Role $role
    ): Entity {
        $entity = $this->createEntity($identifier, $user);

        $this->createQuery()
            ->create(
                [
                    'identifier' => $identifier->value(),
                    'tokenable_id' => $entity->user()->value(),
                    'tokenable_type' => $this->tokenableType,
                    'name' => 'default',
                    'token' => $this->hash($entity->accessToken()->value()),
                    'expires_at' => $entity->accessToken()->expiresAt()->toAtomString(),
                    'refresh_token' => $this->hash($entity->refreshToken()->value()),
                    'refresh_token_expires_at' => $entity->refreshToken()->expiresAt()->toAtomString(),
                    'abilities' => [$role->name],
                ]
            )
        ;

        return $entity;
    }

    /**
     * {@inheritDoc}
     */
    public function find(AuthenticationIdentifier $identifier): Entity
    {
        $record = $this->createQuery()
            ->ofIdentifier($identifier)
            ->first()
        ;

        if (\is_null($record)) {
            throw new \OutOfBoundsException('Authentication not found.');
        }

        return $this->restoreEntity($record);
    }

    /**
     * {@inheritDoc}
     */
    public function introspection(string $value, TokenType $type): bool
    {
        $target = $this->createQuery()
            ->ofToken($value, $type)
            ->first()
        ;

        if (\is_null($target)) {
            throw new InvalidTokenException('Token not found.');
        }

        $now = CarbonImmutable::now();

        if ($type === TokenType::ACCESS) {
            return $now < $target->expires_at;
        }

        return $now < $target->refresh_token_expires_at;
    }

    /**
     * {@inheritDoc}
     */
    public function refresh(string $value, TokenType $type): Entity
    {
        if ($type !== TokenType::REFRESH) {
            throw new InvalidTokenException('Token type must be refresh.');
        }

        $target = $this->createQuery()
            ->ofToken($value, $type)
            ->first()
        ;

        if (\is_null($target) || $target->refresh_token_expires_at < CarbonImmutable::now()) {
            throw new InvalidTokenException('Refresh token is invalid.');
        }

        $next = $this->createEntity(
            identifier: new AuthenticationIdentifier($target->identifier),
            user: new UserIdentifier($target->tokenable_id),
        );

        $target->token = $this->hash($next->accessToken()->value());
        $target->expires_at = $next->accessToken()->expiresAt()->toAtomString();
        $target->refresh_token = $this->hash($next->refreshToken()->value());
        $target->refresh_token_expires_at = $next->refreshToken()->expiresAt()->toAtomString();

        $target->save();

        return $next;
    }

    /**
     * {@inheritDoc}
     */
    public function revoke(string $value, TokenType $type): void
    {
        $target = $this->createQuery()
            ->ofToken($value, $type)
            ->first()
        ;

        if (\is_null($target)) {
            throw new InvalidTokenException('Token not found.');
        }

        $updateValues = match ($type) {
            TokenType::ACCESS => [
                'token' => null,
                'expires_at' => null,
            ],
            TokenType::REFRESH => [
                'refresh_token' => null,
                'refresh_token_expires_at' => null,
            ],
        };

        $target->update($updateValues);
    }

    /**
     * {@inheritDoc}
     */
    public function logout(AuthenticationIdentifier $identifier): void
    {
        $record = $this->builder
            ->ofIdentifier($identifier)
            ->first()
        ;

        if (\is_null($record)) {
            throw new \OutOfBoundsException('Authentication not found.');
        }

        $record->delete();
    }

    /**
     * クエリビルダを生成する.
     */
    private function createQuery()
    {
        return $this->builder->newQuery();
    }

    /**
     * エンティティを生成する.
     */
    private function createEntity(AuthenticationIdentifier $identifier, UserIdentifier $user, ?Token $refreshToken = null): Entity
    {
        return new Entity(
            identifier: $identifier,
            user: $user,
            accessToken: new Token(
                type: TokenType::ACCESS,
                value: $this->generateTokenString(),
                expiresAt: CarbonImmutable::now()
                    ->addMinutes($this->accessTokenTTL)
            ),
            refreshToken: new Token(
                type: TokenType::REFRESH,
                value: $this->generateTokenString(),
                expiresAt: $refreshToken?->expiresAt() ?? CarbonImmutable::now()
                    ->addMinutes($this->refreshTokenTTL)
            )
        );
    }

    /**
     * トークン文字列を生成する.
     */
    private function generateTokenString(): string
    {
        return sprintf(
            '%s%s%s',
            config('sanctum.token_prefix', ''),
            $tokenEntropy = Str::random(40),
            hash('crc32b', $tokenEntropy)
        );
    }

    /**
     * 文字列をハッシュ化する.
     */
    private function hash(string $value): string
    {
        return \hash('sha256', $value);
    }

    /**
     * レコードから認証を復元する.
     */
    private function restoreEntity(Record $record): Entity
    {
        return new Entity(
            identifier: new AuthenticationIdentifier($record->identifier),
            user: new UserIdentifier($record->tokenable_id),
            accessToken: $this->restoreToken($record, TokenType::ACCESS),
            refreshToken: $this->restoreToken($record, TokenType::REFRESH),
        );
    }

    /**
     * レコードからトークンを復元する.
     */
    private function restoreToken(Record $record, TokenType $type): Token
    {
        return new Token(
            type: $type,
            value: $record->token,
            expiresAt: CarbonImmutable::parse(
                $type === TokenType::ACCESS
                    ? $record->expires_at
                    : $record->refresh_token_expires_at
            )
        );
    }
}
