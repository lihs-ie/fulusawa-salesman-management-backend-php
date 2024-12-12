<?php

namespace App\Domains\Authentication;

use App\Domains\Authentication\Entities\Authentication;
use App\Domains\Authentication\ValueObjects\AuthenticationIdentifier;
use App\Domains\Authentication\ValueObjects\TokenType;
use App\Domains\User\ValueObjects\Role;
use App\Domains\User\ValueObjects\UserIdentifier;

/**
 * 認証リポジトリ.
 */
interface AuthenticationRepository
{
    /**
     * 認証を永続化する.
     *
     * @throws \UniqueConstraintViolationException テーブルに既に識別子が使用されている場合
     */
    public function persist(
        AuthenticationIdentifier $identifier,
        UserIdentifier $user,
        Role $role
    ): Authentication;

    /**
     * 認証を取得する.
     *
     * @throws \OutOfBoundsException 認証情報が存在しない場合
     */
    public function find(AuthenticationIdentifier $identifier): Authentication;

    /**
     * トークンが有効か確認する.
     *
     * @throws InvalidTokenException トークンが不正な場合
     */
    public function introspection(string $value, TokenType $type): bool;

    /**
     * 認証を更新する.
     *
     * @throws InvalidTokenException トークンが不正な場合
     */
    public function refresh(string $value, TokenType $type): Authentication;

    /**
     * トークンを破棄する.
     *
     * @throws InvalidTokenException トークンが不正な場合
     */
    public function revoke(string $value, TokenType $type): void;

    /**
     * ログアウトする.
     *
     * @throws \OutOfBoundsException 認証情報が存在しない場合
     */
    public function logout(AuthenticationIdentifier $identifier): void;
}
