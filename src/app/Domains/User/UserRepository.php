<?php

namespace App\Domains\User;

use App\Domains\User\Entities\User;
use App\Domains\User\ValueObjects\UserIdentifier;
use Illuminate\Support\Enumerable;

/**
 * ユーザーリポジトリ
 */
interface UserRepository
{
    /**
     * ユーザーを永続化する
     *
     * @param User $user
     * @return void
     *
     * @throws \AuthorizationException 権限がない場合
     */
    public function persist(User $user): void;

    /**
     * ユーザーを取得する
     *
     * @param UserIdentifier $identifier
     * @return User
     *
     * @throws \OutOfBoundsException ユーザーが存在しない場合
     */
    public function find(UserIdentifier $identifier): User;

    /**
     * ユーザー一覧を取得する
     *
     * @return Enumerable
     *
     * @throws \AuthorizationException 権限がない場合
     */
    public function list(): Enumerable;

    /**
     * ユーザーを削除する
     *
     * @param UserIdentifier $identifier
     * @return void
     *
     * @throws \AuthorizationException 権限がない場合
     */
    public function delete(UserIdentifier $identifier): void;
}
