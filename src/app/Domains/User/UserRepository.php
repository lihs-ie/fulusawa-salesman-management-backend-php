<?php

namespace App\Domains\User;

use App\Domains\User\Entities\User;
use App\Domains\User\ValueObjects\UserIdentifier;
use Illuminate\Support\Enumerable;

/**
 * ユーザーリポジトリ.
 */
interface UserRepository
{
    /**
     * ユーザーを永続化する.
     *
     * @throws ConflictException ユーザーが既に存在する場合|メールアドレスが既に使用されている場合
     */
    public function add(User $user): void;

    /**
     * ユーザーを更新する.
     *
     * @throws \OutOfBoundsException ユーザーが存在しない場合
     * @throws ConflictException     メールアドレスが既に使用されている場合
     */
    public function update(User $user): void;

    /**
     * ユーザーを取得する.
     *
     * @throws \OutOfBoundsException ユーザーが存在しない場合
     */
    public function find(UserIdentifier $identifier): User;

    /**
     * ユーザー一覧を取得する.
     */
    public function list(): Enumerable;

    /**
     * ユーザーを削除する.
     */
    public function delete(UserIdentifier $identifier): void;
}
