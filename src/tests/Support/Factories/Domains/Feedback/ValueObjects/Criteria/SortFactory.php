<?php

namespace Tests\Support\Factories\Domains\Feedback\ValueObjects\Criteria;

use App\Domains\Feedback\ValueObjects\Criteria\Sort;
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
