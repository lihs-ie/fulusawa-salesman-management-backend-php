<?php

namespace Tests\Support\Assertions;

/**
 * データベースレコードの比較を行う機能.
 */
trait DatabaseComparable
{
    /**
     * レコードの永続化を確認し、対象のレコードを返すヘルパ.
     */
    protected function assertDatabaseHasRecord(string $table, array $expected): object
    {
        $this->assertDatabaseHas($table, $expected);

        return $this->getConnection()->table($table)->where($expected)->first();
    }
}
