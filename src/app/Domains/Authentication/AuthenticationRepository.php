<?php

namespace App\Domains\Authentication;

use App\Domains\Authentication\Entities\Authentication;
use App\Domains\Authentication\ValueObjects\AuthenticationIdentifier;
use App\Domains\Authentication\ValueObjects\Token;
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
     * @param MailAddress $email
     * @param string $password
     * @return Authentication
     */
    public function persist(AuthenticationIdentifier $identifier, MailAddress $email, string $password): Authentication;

    /**
     * 認証を取得する
     *
     * @param AuthenticationIdentifier $identifier
     * @return Authentication
     */
    public function find(AuthenticationIdentifier $identifier): Authentication;

    /**
     * トークンが有効か確認する
     *
     * @param Token $token
     * @return bool
     */
    public function introspection(Token $token): bool;

    /**
     * 認証を更新する
     *
     * @param Token $token
     * @return Authentication
     *
     * @throws \UnexpectedValueException トークン種別がリフレッシュトークンでない場合|トークンが有効期限切れの場合
     * @throws \OutOfBoundsException トークンが存在しない場合
     * @throws \RuntimeException トークンが既に使用済みの場合
     */
    public function refresh(Token $token): Authentication;

    /**
     * トークンを破棄する
     *
     * @param Token $token
     * @return void
     */
    public function revoke(Token $token): void;
}
