<?php

namespace App\Domains\Authentication\Entities;

use App\Domains\Authentication\ValueObjects\AccessTokenIdentifier;
use App\Domains\User\ValueObjects\Role;

/**
 * アクセストークンを表すエンティティ
 */
class AccessToken
{
    public function __construct(
        public readonly AccessTokenIdentifier $identifier,
        public readonly \DateTimeInterface $expiresAt,
        public readonly Role $role
    ) {
    }
}
