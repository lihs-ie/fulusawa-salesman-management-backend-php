<?php

namespace App\Domains\Visit;

use App\Domains\Visit\Entities\Visit;
use App\Domains\Visit\ValueObjects\Criteria;
use App\Domains\Visit\ValueObjects\VisitIdentifier;
use Illuminate\Support\Enumerable;

/**
 * 訪問リポジトリ.
 */
interface VisitRepository
{
    /**
     * 訪問を追加する.
     *
     * @throws ConflictException 訪問が既に存在する場合
     */
    public function add(Visit $visit): void;

    /**
     * 訪問を更新する.
     *
     * @throws \OutOfBoundsException 訪問が存在しない場合
     */
    public function update(Visit $visit): void;

    /**
     * 訪問を取得する.
     *
     * @throws \OutOfBoundsException 訪問が存在しない場合
     */
    public function find(VisitIdentifier $identifier): Visit;

    /**
     * 訪問を一覧で取得する.
     */
    public function list(Criteria $criteria): Enumerable;

    /**
     * 訪問を削除する.
     *
     * @throws \OutOfBoundsException 訪問が存在しない場合
     */
    public function delete(VisitIdentifier $identifier): void;
}
