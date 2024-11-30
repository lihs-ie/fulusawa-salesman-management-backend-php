<?php

namespace App\Infrastructures\Authentication;

use App\Domains\Authentication\AuthenticationRepository;
use App\Domains\Authentication\Entities\Authentication as Entity;
use App\Domains\Authentication\ValueObjects\AuthenticationIdentifier;
use App\Domains\Authentication\ValueObjects\Token;
use App\Domains\Authentication\ValueObjects\TokenType;
use App\Domains\Common\ValueObjects\MailAddress;
use App\Domains\User\ValueObjects\UserIdentifier;
use App\Exceptions\InvalidTokenException;
use App\Infrastructures\Authentication\Models\Authentication as Record;
use App\Infrastructures\User\Models\User as UserRecord;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * 認証リポジトリ
 */
class EloquentAuthenticationRepository implements AuthenticationRepository
{
    /**
     * コンストラクタ.
     */
    public function __construct(
        private readonly Record $builder,
        private readonly UserRecord $userBuilder,
        private readonly int $accessTokenTTL,
        private readonly int $refreshTokenTTL
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function persist(
        AuthenticationIdentifier $identifier,
        MailAddress $email,
        string $password
    ): Entity {
        $userRecord = $this->getUserOfCredential($email, $password);

        $entity = $this->createEntity($identifier, new UserIdentifier($userRecord->identifier));

        $this->builder
          ->create(
              [
              'identifier' => $identifier->value(),
              'tokenable_id' => $entity->user()->value(),
              'tokenable_type' => UserRecord::class,
              'name' => $userRecord->last_name . $userRecord->first_name,
              'token' => hash('sha256', $entity->accessToken()->value()),
              'expires_at' => $entity->accessToken()->expiresAt()->toAtomString(),
              'refresh_token' => hash('sha256', $entity->refreshToken()->value()),
              'refresh_token_expires_at' => $entity->refreshToken()->expiresAt()->toAtomString(),
              'abilities' => [$userRecord->role],
        ]
          );

        return $entity;
    }

    /**
     * {@inheritDoc}
     */
    public function find(AuthenticationIdentifier $identifier): Entity
    {
        $record = $this->createAuthenticationQuery()
          ->where('identifier', $identifier->value())
          ->first();

        if (\is_null($record)) {
            throw new \OutOfBoundsException('Authentication not found.');
        }

        return $this->restoreEntity($record);
    }

    /**
     * {@inheritDoc}
     */
    public function introspection(Token $token): bool
    {
        $target = $this->createAuthenticationQuery()
          ->when(
              $token->type() === TokenType::ACCESS,
              fn (Builder $query) => $query->where('token', hash('sha256', $token->value()))
          )
          ->when(
              $token->type() === TokenType::REFRESH,
              fn (Builder $query) => $query->where('refresh_token', hash('sha256', $token->value()))
          )
          ->first();

        if (\is_null($target)) {
            throw new InvalidTokenException('Token not found.');
        }

        $now = CarbonImmutable::now();

        if ($token->type() === TokenType::ACCESS) {
            return $now < $target->expires_at;
        }

        return $now < $target->refresh_token_expires_at;
    }

    /**
     * {@inheritDoc}
     */
    public function refresh(Token $token): Entity
    {
        if ($token->type() !== TokenType::REFRESH) {
            throw new InvalidTokenException('Token type must be refresh.');
        }

        $record = $this->createAuthenticationQuery()
          ->where('refresh_token', hash('sha256', $token->value()))
          ->first();

        if (\is_null($record) || $record->refresh_token_expires_at < CarbonImmutable::now()) {
            throw new InvalidTokenException('Refresh token is invalid.');
        }

        $existence = $this->restoreEntity($record);

        $next = $this->createEntity(
            identifier: new AuthenticationIdentifier($record->identifier),
            user: $existence->user(),
            refreshToken: $existence->refreshToken()
        );

        $this->builder
          ->where('identifier', $next->identifier()->value())
          ->update([
            'token' => $next->accessToken()->value(),
            'expires_at' => $next->accessToken()->expiresAt()->toAtomString(),
            'refresh_token' => hash('sha256', $next->refreshToken()->value()),
            'refresh_token_expires_at' => $next->refreshToken()->expiresAt()->toAtomString(),
          ]);

        return $next;
    }

    /**
     * {@inheritDoc}
     */
    public function revoke(Token $token): void
    {
        $target = $this->createAuthenticationQuery()
          ->when(
              $token->type() === TokenType::ACCESS,
              fn (Builder $query) => $query->where('token', hash('sha256', $token->value()))
          )
          ->when(
              $token->type() === TokenType::REFRESH,
              fn (Builder $query) => $query->where('refresh_token', hash('sha256', $token->value()))
          );

        if (\is_null($target)) {
            throw new InvalidTokenException('Token not found.');
        }

        $updateValues = match ($token->type()) {
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
          ->where('identifier', $identifier->value())
          ->first();

        if (\is_null($record)) {
            throw new \OutOfBoundsException('Authentication not found.');
        }

        $record->delete();
    }

    /**
     * クエリビルダを生成する.
     */
    private function createAuthenticationQuery()
    {
        return $this->builder->with('user');
    }

    /**
     * ユーザークエリビルダを生成する.
     */
    private function createUserQuery(): Builder
    {
        return $this->userBuilder->newQuery();
    }

    /**
     * クレデンシャルからユーザーを取得する.
     */
    private function getUserOfCredential(MailAddress $email, string $password): UserRecord
    {
        $target = $this->createUserQuery()
          ->where('email', $email->value())
          ->where('password', $password)
          ->first();

        if (\is_null($target)) {
            throw new AuthorizationException('Invalid credentials.');
        }

        return $target;
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
