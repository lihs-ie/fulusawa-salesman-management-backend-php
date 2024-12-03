<?php

namespace App\Domains\Common\Utils;

use Illuminate\Support\Enumerable;

/**
 * コレクション型の値に関するユーティリティ.
 */
class CollectionUtil
{
    /**
     * コレクションをSetとして比較する.
     */
    public static function equalsAsSet(
        Enumerable $left,
        Enumerable $right,
        ?\Closure $comparator = null,
        ?\Closure $sorter = null
    ): bool {
        if (\is_null($comparator)) {
            $comparator = static::defaultComparator();
        }

        return $left->sort($sorter)->values()
            ->zip($right->sort($sorter)->values())
            ->every(function (Enumerable $pair) use ($comparator): bool {
                [$own, $foreign] = $pair->all();

                if (\is_null($own) || \is_null($foreign)) {
                    return false;
                }

                return $comparator($own, $foreign);
            })
        ;
    }

    /**
     * デフォルトの比較関数.
     */
    public static function defaultComparator(): \Closure
    {
        return fn ($own, $foreign): bool => $own === $foreign;
    }
}
