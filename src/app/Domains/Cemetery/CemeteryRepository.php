<?php

namespace App\Domains\Cemetery;

use App\Domains\Cemetery\Entities\Cemetery;
use App\Domains\Cemetery\ValueObjects\CemeteryIdentifier;
use App\Domains\Cemetery\ValueObjects\Criteria;
use Illuminate\Support\Enumerable;

/**
 * 墓地情報リポジトリ
 */
interface CemeteryRepository
{
    /**
     * 新規の墓地情報を永続化する
     *
     * @param Cemetery $cemetery
     * @return void
     *
     * @throws \UnexpectedValueException 墓地情報の各値が不正な場合
     * @throws \ConflictException 墓地情報識別子が重複している場合
     */
    public function add(Cemetery $cemetery): void;

    /**
     * 墓地情報を更新する
     *
     * @param Cemetery $cemetery
     * @return void
     *
     * @throws \OutOfBoundsException 墓地情報が存在しない場合
     * @throws \UnexpectedValueException 墓地情報の各値が不正な場合
     */
    public function update(Cemetery $cemetery): void;

    /**
     * 墓地情報を取得する
     *
     * @param CemeteryIdentifier $identifier
     * @return Cemetery
     *
     * @throws \OutOfBoundsException 墓地情報が存在しない場合
     */
    public function find(CemeteryIdentifier $identifier): Cemetery;

    /**
     * 墓地情報一覧を取得する
     *
     * @param Criteria $criteria
     * @return Enumerable
     */
    public function list(Criteria $criteria): Enumerable;

    /**
     * 墓地情報を削除する
     *
     * @param CemeteryIdentifier $identifier
     * @return void
     *
     * @throws \OutOfBoundsException 墓地情報が存在しない場合
     */
    public function delete(CemeteryIdentifier $identifier): void;
}
