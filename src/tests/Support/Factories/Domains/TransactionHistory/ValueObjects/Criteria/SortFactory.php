<?php

namespace Tests\Support\Factories\Domains\TransactionHistory\ValueObjects\Criteria;

use App\Domains\TransactionHistory\ValueObjects\Criteria\Sort;
use Tests\Support\Factories\EnumFactory;

/**
 * テスト用のソート条件ファクトリ.
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
