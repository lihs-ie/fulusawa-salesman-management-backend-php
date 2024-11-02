<?php

namespace Tests\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use MyCLabs\Enum\Enum;

/**
 * テスト用のランダムな値を生成するユーティリティ.
 *
 * Fakerでは冗長になる部分、パフォーマンスの悪い部分を補完する
 */
class Randomizer
{
    /**
     * 指定した個数の重複しないランダムな数値を生成する.
     */
    public static function createUniqueNumbers(
        int $count,
        int $min = 1,
        int $max = PHP_INT_MAX
    ): Enumerable {
        if ($count < 0) {
            throw new \InvalidArgumentException(sprintf(
                'Count must be zero or positive. %s is given.',
                $count
            ));
        }

        if ($max - $min + 1 < $count) {
            throw new \InvalidArgumentException(sprintf(
                'Range of candidates must be larger than count. Now %d - %d < %d.',
                $max,
                $min,
                $count
            ));
        }

        return Collection::times($count)
            ->reduce(function (Collection $carry, int $index) use ($min, $max): Collection {
                $value = $carry->reduce(function (int $current, int $comparison): int {
                    if ($current >= $comparison) {
                        return $current + 1;
                    }

                    return $current;
                }, mt_rand($min, $max - $index + 1));

                return $carry->concat([$value])->sort()->values();
            }, new Collection())
            ->shuffle()
        ;
    }

    /**
     * 指定された列挙型クラスからランダムな値を生成する.
     *
     * @throws \InvalidArgumentException 指定されたクラスが列挙型でないとき
     */
    public static function choose(string $class, ?Enum $exclude = null): Enum
    {
        if (!is_subclass_of($class, Enum::class)) {
            throw new \InvalidArgumentException(sprintf(
                'Class %s is not subclass of %s.',
                $class,
                Enum::class
            ));
        }

        return Collection::make($class::values())
            ->when($exclude, fn ($candidates) => $candidates->reject($exclude))
            ->random()
        ;
    }
}
