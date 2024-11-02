<?php

namespace Tests\Support;

use Illuminate\Support\Enumerable;

/**
 * テスト用のインスタンスを生成するファクトリの基底クラス.
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
abstract class DependencyFactory
{
    /**
     * 対象クラスのインスタンスをランダムに生成する.
     */
    abstract public function create(DependencyBuilder $builder, int $seed, array $overrides);

    /**
     * 対象クラスの重複しないインスタンスのリストを生成する.
     */
    public function createList(
        DependencyBuilder $builder,
        int $count,
        array $overrides,
        int $min,
        int $max
    ): Enumerable {
        return Randomizer::createUniqueNumbers($count, $min, $max)
            ->map(function (int $seed) use ($builder, $overrides) {
                return $this->create($builder, $seed, $overrides);
            })
        ;
    }

    /**
     * 指定したインスタンスを複製する.
     */
    abstract public function duplicate(DependencyBuilder $builder, $instance, array $overrides);

    /**
     * seed値の範囲を取得する.
     */
    public function range(): array
    {
        return ['min' => 1, 'max' => \PHP_INT_MAX];
    }
}
