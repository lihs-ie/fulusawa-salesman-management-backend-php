<?php

namespace App\Domains\Authentication\Entities;

use App\Domains\Authentication\ValueObjects\AuthenticationIdentifier;
use App\Domains\Authentication\ValueObjects\RefreshToken;
use App\Domains\Authentication\ValueObjects\Token;

/**
 * 認証エンティティ
 */
class Authentication
{
    public function __construct(
        public readonly AuthenticationIdentifier $identifier,
        public readonly Token $accessToken,
        public readonly Token $refreshToken,
    ) {
    }

    public function identifier(): AuthenticationIdentifier
    {
        return $this->identifier;
    }

    public function accessToken(): Token
    {
        return $this->accessToken;
    }

    public function refreshToken(): Token
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

        if (!$this->accessToken->equals($other->accessToken)) {
            return false;
        }

        if (!$this->refreshToken->equals($other->refreshToken)) {
            return false;
        }

        return true;
    }
}
