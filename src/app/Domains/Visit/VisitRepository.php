<?php

namespace App\Domains\Visit;

use App\Domains\User\ValueObjects\UserIdentifier;
use App\Domains\Visit\Entities\Visit;
use App\Domains\Visit\ValueObjects\VisitIdentifier;
use Illuminate\Support\Enumerable;

/**
 * 訪問リポジトリ
 */
interface VisitRepository
{
    /**
     * 訪問を永続化する.
     *
     * @param Visit $visit
     * @return void
     */
    public function persist(Visit $visit): void;

    /**
     * 訪問を取得する.
     *
     * @param VisitIdentifier $identifier
     * @return Visit
     *
     * @throws \OutOfBoundsException 訪問が存在しない場合
     */
    public function find(VisitIdentifier $identifier): Visit;

    /**
     * 訪問を一覧で取得する.
     *
     * @return Enumerable
     */
    public function list(): Enumerable;

    /**
     * 訪問を削除する.
     *
     * @param VisitIdentifier $identifier
     * @return void
     *
     * @throws \OutOfBoundsException 訪問が存在しない場合
     */
    public function delete(VisitIdentifier $identifier): void;

    /**
     * ユーザーの訪問を取得する.
     *
     * @param UserIdentifier $user
     * @return Enumerable
     *
     * @throws \OutOfBoundsException ユーザーが存在しない場合
     */
    public function ofUser(UserIdentifier $user): Enumerable;
}
