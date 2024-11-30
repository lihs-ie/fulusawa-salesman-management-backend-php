<?php

namespace App\UseCases;

use App\Domains\Authentication\AuthenticationRepository;
use App\Domains\Authentication\Entities\Authentication as Entity;
use App\Domains\Authentication\ValueObjects\AuthenticationIdentifier;
use App\Domains\Authentication\ValueObjects\Token;
use App\Domains\Authentication\ValueObjects\TokenType;
use App\Domains\Common\ValueObjects\MailAddress;
use App\UseCases\Factories\CommonDomainFactory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Enumerable;

/**
 * 認証ユースケース
 */
class Authentication
{
    use CommonDomainFactory;

    public function __construct(
        private readonly AuthenticationRepository $repository,
    ) {
    }

    /**
     * アクセストークンを発行・永続化する
     *
     * @param string $identifier
     * @param string $email
     * @param string $password
     * @return Entity
     */
    public function persist(string $identifier, string $email, string $password): Entity
    {
        return $this->repository->persist(
            identifier: new AuthenticationIdentifier($identifier),
            email: new MailAddress($email),
            password: $password
        );
    }

    /**
     * トークンが有効か検証する
     *
     * @param array $token
     * @return bool
     */
    public function introspection(array $token): bool
    {
        return $this->repository->introspection($this->extractToken($token));
    }

    /**
     * アクセストークンを更新する
     *
     * @param array $token
     * @return Entity
     */
    public function refresh(array $token): Entity
    {
        return $this->repository->refresh($this->extractToken($token));
    }

    /**
     * アクセストークンを破棄する
     *
     * @param array $token
     * @return void
     */
    public function revoke(array $token): void
    {
        $this->repository->revoke($this->extractToken($token));
    }

    /**
     * ログアウトする
     *
     * @param string $identifier
     * @return void
     */
    public function logout(string $identifier): void
    {
        $this->repository->logout(new AuthenticationIdentifier($identifier));
    }

    /**
     * 配列からトークンを抽出する
     *
     * @param array $token
     * @return Token
     */
    private function extractToken(array $token): Token
    {
        $type = match ($token['type']) {
            TokenType::ACCESS->name => TokenType::ACCESS,
            TokenType::REFRESH->name => TokenType::REFRESH,
        };

        return new Token(
            type: $type,
            value: $token['value'],
            expiresAt: CarbonImmutable::parse($token['expiresAt'])
        );
    }
}
