<?php

namespace App\Domains\Authentication;

use App\Domains\Authentication\Entities\AccessToken;
use App\Domains\Common\ValueObjects\MailAddress;

/**
 * 認証リポジトリ
 */
interface AuthenticationRepository
{
    public function token(MailAddress $mail, string $password): AccessToken;

    public function me(AccessToken $token): bool;

    public function revoke(AccessToken $token): void;
}
