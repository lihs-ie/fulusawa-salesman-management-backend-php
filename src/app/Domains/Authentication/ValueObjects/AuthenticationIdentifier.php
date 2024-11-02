<?php

namespace App\Domains\Authentication\ValueObjects;

use App\Domains\Common\ValueObjects\UniversallyUniqueIdentifier;

/**
 * 認証識別子
 */
class AuthenticationIdentifier extends UniversallyUniqueIdentifier
{
    public function __construct(
        string $value
    ) {
        parent::__construct($value);
    }
}
