<?php

namespace App\Domains\Authentication;

use App\Domains\Authentication\Entities\Authentication;
use App\Domains\Authentication\ValueObjects\AuthenticationIdentifier;
use App\Domains\Authentication\ValueObjects\Token;
use App\Domains\Common\ValueObjects\MailAddress;

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
     *
     * @throws \AuthorizationException メールアドレスまたはパスワードが不正な場合
     * @throws \UniqueConstraintViolationException テーブルに既に識別子が使用されている場合
     */
    public function persist(AuthenticationIdentifier $identifier, MailAddress $email, string $password): Authentication;

    /**
     * 認証を取得する
     *
     * @param AuthenticationIdentifier $identifier
     * @return Authentication
     *
     * @throws \OutOfBoundsException 認証情報が存在しない場合
     */
    public function find(AuthenticationIdentifier $identifier): Authentication;

    /**
     * トークンが有効か確認する
     *
     * @param Token $token
     * @return bool
     *
     * @throws InvalidTokenException トークンが不正な場合
     */
    public function introspection(Token $token): bool;

    /**
     * 認証を更新する
     *
     * @param Token $token
     * @return Authentication
     *
     * @throws InvalidTokenException トークンが不正な場合
     */
    public function refresh(Token $token): Authentication;

    /**
     * トークンを破棄する
     *
     * @param Token $token
     * @return void
     *
     * @throws InvalidTokenException トークンが不正な場合
     */
    public function revoke(Token $token): void;

    /**
     * ログアウトする
     *
     * @param AuthenticationIdentifier $identifier
     * @return void
     *
     * @throws \OutOfBoundsException 認証情報が存在しない場合
     */
    public function logout(AuthenticationIdentifier $identifier): void;
}
