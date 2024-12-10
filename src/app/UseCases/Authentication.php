<?php

namespace App\UseCases;

use App\Domains\Authentication\AuthenticationRepository;
use App\Domains\Authentication\Entities\Authentication as Entity;
use App\Domains\Authentication\ValueObjects\AuthenticationIdentifier;
use App\Domains\Authentication\ValueObjects\TokenType;
use App\Domains\Common\ValueObjects\MailAddress;
use App\Domains\User\UserRepository;
use App\UseCases\Factories\CommonDomainFactory;

/**
 * 認証ユースケース.
 */
class Authentication
{
    use CommonDomainFactory;

    public function __construct(
        private readonly AuthenticationRepository $authRepository,
        private readonly UserRepository $userRepository
    ) {}

    /**
     * アクセストークンを発行・永続化する.
     */
    public function persist(string $identifier, string $email, string $password): Entity
    {
        $user = $this->userRepository->ofCredentials(new MailAddress($email), $password);

        return $this->authRepository->persist(
            identifier: new AuthenticationIdentifier($identifier),
            user: $user->identifier(),
            role: $user->role()
        );
    }

    /**
     * トークンが有効か検証する.
     */
    public function introspection(string $value, string $type): bool
    {
        return $this->authRepository->introspection(
            value: $value,
            type: $this->convertTokenType($type)
        );
    }

    /**
     * アクセストークンを更新する.
     */
    public function refresh(string $value, string $type): Entity
    {
        return $this->authRepository->refresh(
            value: $value,
            type: $this->convertTokenType($type)
        );
    }

    /**
     * アクセストークンを破棄する.
     */
    public function revoke(string $value, string $type): void
    {
        $this->authRepository->revoke(
            value: $value,
            type: $this->convertTokenType($type)
        );
    }

    /**
     * ログアウトする.
     */
    public function logout(string $identifier): void
    {
        $this->authRepository->logout(new AuthenticationIdentifier($identifier));
    }

    /**
     * 配列からトークンを抽出する.
     */
    private function convertTokenType(string $type): TokenType
    {
        return match ($type) {
            TokenType::ACCESS->name => TokenType::ACCESS,
            TokenType::REFRESH->name => TokenType::REFRESH,
        };
    }
}
