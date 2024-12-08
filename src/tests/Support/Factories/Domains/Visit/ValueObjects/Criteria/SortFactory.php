<?php

namespace Tests\Support\Factories\Domains\Visit\ValueObjects\Criteria;

use App\Domains\Visit\ValueObjects\Criteria\Sort;
use Tests\Support\Factories\EnumFactory;

/**
 * テスト用のソート条件を生成するファクトリ.
 */
class SortFactory extends EnumFactory
{
    /**
     * {@inheritdoc}
     */
    protected function target(): string
    {
        return Sort::class;
    }
}
