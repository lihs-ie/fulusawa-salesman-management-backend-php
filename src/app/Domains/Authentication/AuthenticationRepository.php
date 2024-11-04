<?php

namespace App\Domains\Authentication;

use App\Domains\Authentication\Entities\Authentication;
use App\Domains\Authentication\ValueObjects\AuthenticationIdentifier;
use App\Domains\Common\ValueObjects\MailAddress;
use Illuminate\Support\Enumerable;

/**
 * 認証リポジトリ
 */
interface AuthenticationRepository
{
    /**
     * 認証を永続化する
     *
     * @param AuthenticationIdentifier $identifier
     * @param MailAddress $mail
     * @param string $password
     * @return Authentication
     */
    public function persist(AuthenticationIdentifier $identifier, MailAddress $mail, string $password): Authentication;

    /**
     * 認証を取得する
     *
     * @param AuthenticationIdentifier $identifier
     * @return Authentication
     */
    public function find(AuthenticationIdentifier $identifier): Authentication;

    /**
     * 認証が有効か確認する
     *
     * @param Authentication $authentication
     * @return Enumerable
     *
     * ※ 捩り値は['accessToken' => bool, 'refreshToken' => bool]の形式で返す
     */
    public function introspection(Authentication $authentication): Enumerable;

    /**
     * 認証を更新する
     *
     * @param AuthenticationIdentifier $identifier
     * @return Authentication
     */
    public function refresh(AuthenticationIdentifier $identifier): Authentication;

    /**
     * 認証を破棄する
     *
     * @param AuthenticationIdentifier $identifier
     * @return void
     */
    public function revoke(AuthenticationIdentifier $identifier): void;
}
