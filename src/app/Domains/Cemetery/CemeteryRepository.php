<?php

namespace App\Domains\Cemetery;

use App\Domains\Cemetery\Entities\Cemetery;
use App\Domains\Cemetery\ValueObjects\CemeteryIdentifier;
use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use Illuminate\Support\Enumerable;

/**
 * 墓地情報リポジトリ
 */
interface CemeteryRepository
{
    /**
     * 墓地情報を永続化する
     *
     * @param Cemetery $cemetery
     * @return void
     */
    public function persist(Cemetery $cemetery): void;

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
     * 顧客識別子を指定して墓地情報を取得する
     *
     * @param CustomerIdentifier $customer
     * @return Enumerable
     */
    public function ofCustomer(CustomerIdentifier $customer): Enumerable;

    /**
     * 墓地情報一覧を取得する
     *
     * @return Enumerable
     */
    public function list(): Enumerable;

    /**
     * 墓地情報を削除する
     *
     * @param CemeteryIdentifier $identifier
     * @return void
     *
     * @throws \OutOfBoundsException 墓地情報が存在しない場合
     * @throws \AuthorizationException 権限がない場合
     */
    public function delete(CemeteryIdentifier $identifier): void;
}
