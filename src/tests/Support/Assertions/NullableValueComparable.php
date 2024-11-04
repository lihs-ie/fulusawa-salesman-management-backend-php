<?php

namespace Tests\Support\Assertions;

use Closure;

/**
 * nullableな値オブジェクトの比較を行う機能.
 */
trait NullableValueComparable
{
    /**
     * nullableな値の比較を行う.
     */
    protected function assertNullOr($expected, $actual, Closure $comparer): void
    {
        if (\is_null($expected)) {
            $this->assertNull($actual);
        } else {
            $this->assertNotNull($actual);
            $comparer($expected, $actual);
        }
    }

    /**
     * nullableな値の同値性を検証する.
     *
     * nullでないとき、指定した関数で変換を行った上で比較することができる
     */
    protected function assertNullOrSame($expected, $actual, ?Closure $transform = null): void
    {
        if (\is_null($transform)) {
            $transform = fn ($value) => $value;
        }

        $this->assertNullOr($expected, $actual, function ($e, $a) use ($transform): void {
            $this->assertSame($e, $transform($a));
        });
    }

    /**
     * nullableな値の比較を行う.
     *
     * 期待する値がnullの場合は常に成功とする
     */
    protected function assertAnyOr($expected, $actual, Closure $comparer): void
    {
        if (\is_null($expected)) {
            $this->assertTrue(true);
        } else {
            $comparer($expected, $actual);
        }
    }

    /**
     * nullableな値の同値性を検証する.
     *
     * 期待する値がnullの場合は常に成功とする
     */
    protected function assertAnyOrSame($expected, $actual, ?Closure $transform = null): void
    {
        if (\is_null($transform)) {
            $transform = fn ($value) => $value;
        }

        $this->assertAnyOr($expected, $actual, function ($e, $a) use ($transform): void {
            $this->assertSame($e, $transform($a));
        });
    }
}
