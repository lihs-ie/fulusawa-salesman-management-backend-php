<?php

namespace App\UseCases;

use App\Domains\Authentication\AuthenticationRepository;
use App\Domains\Authentication\Entities\Authentication as Entity;
use App\Domains\Authentication\ValueObjects\AuthenticationIdentifier;
use App\Domains\Authentication\ValueObjects\Token;
use App\Domains\Common\ValueObjects\MailAddress;
use App\UseCases\Factories\CommonDomainFactory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Enumerable;

/**
 * 認証ユースケース
 */
class Authentication
{
    public function __construct(
        private readonly AuthenticationRepository $repository,
        private readonly CommonDomainFactory $factory
    ) {
    }

    /**
     * アクセストークンを発行・永続化する
     *
     * @param string $identifier
     * @param string $mail
     * @param string $password
     * @return string
     */
    public function persist(string $identifier, string $mail, string $password): Entity
    {
        return $this->repository->persist(
            identifier: new AuthenticationIdentifier($identifier),
            mail: new MailAddress($mail),
            password: $password
        );
    }

    /**
     * 認証が有効かどうかを検証する
     *
     * @param string $identifier
     * @param array $accessToken
     * @param array $refreshToken
     * @return Enumerable
     */
    public function introspection(string $identifier, array $accessToken, array $refreshToken): Enumerable
    {
        $entity = new Entity(
            identifier: new AuthenticationIdentifier($identifier),
            accessToken: $this->extractToken($accessToken),
            refreshToken: $this->extractToken($refreshToken)
        );

        return $this->repository->introspection($entity);
    }

    /**
     * アクセストークンを更新する
     *
     * @param string $identifier
     * @return Entity
     */
    public function refresh(string $identifier): Entity
    {
        return $this->repository->refresh(new AuthenticationIdentifier($identifier));
    }

    /**
     * アクセストークンを破棄する
     *
     * @param string $identifier
     * @return void
     */
    public function revoke(string $identifier): void
    {
        $this->repository->revoke(new AuthenticationIdentifier($identifier));
    }

    /**
     * 配列からトークンを抽出する
     *
     * @param array $token
     * @return Token
     */
    private function extractToken(array $token): Token
    {
        return new Token(
            value: $token['value'],
            expiresAt: CarbonImmutable::parse($token['expiresAt'])
        );
    }
}
