<?php

namespace App\Domains\Authentication\Entities;

use App\Domains\Authentication\ValueObjects\AuthenticationIdentifier;
use App\Domains\Authentication\ValueObjects\Token;
use App\Domains\Authentication\ValueObjects\TokenType;
use App\Domains\User\ValueObjects\UserIdentifier;

/**
 * 認証エンティティ
 */
class Authentication
{
    public function __construct(
        public readonly AuthenticationIdentifier $identifier,
        public readonly UserIdentifier $user,
        public readonly Token|null $accessToken,
        public readonly Token|null $refreshToken,
    ) {

        if (!\is_null($accessToken) && $accessToken->type() !== TokenType::ACCESS) {
            throw new \InvalidArgumentException(\sprintf("Access token's type must be %s.", TokenType::ACCESS->name));
        }

        if (!\is_null($refreshToken) && $refreshToken->type() !== TokenType::REFRESH) {
            throw new \InvalidArgumentException(\sprintf("Refresh token's type must be %s.", TokenType::REFRESH->name));
        }
    }

    public function identifier(): AuthenticationIdentifier
    {
        return $this->identifier;
    }

    public function user(): UserIdentifier
    {
        return $this->user;
    }

    public function accessToken(): Token|null
    {
        return $this->accessToken;
    }

    public function refreshToken(): Token|null
    {
        return $this->refreshToken;
    }

    public function equals(?self $other): bool
    {
        if (is_null($other)) {
            return false;
        }

        if (!$this->identifier->equals($other->identifier)) {
            return false;
        }

        if (!$this->user->equals($other->user)) {
            return false;
        }

        if (!$this->accessToken->equals($other->accessToken)) {
            return false;
        }

        if (!$this->refreshToken->equals($other->refreshToken)) {
            return false;
        }

        return true;
    }
}
