<?php

namespace Tests\Support\Factories\Http\Encoders\TransactionHistory;

use App\Http\Encoders\TransactionHistory\TransactionHistoryEncoder;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用の取引履歴エンコーダを生成するファクトリ.
 */
class TransactionHistoryEncoderFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): TransactionHistoryEncoder
    {
        return new TransactionHistoryEncoder();
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): TransactionHistoryEncoder
    {
        throw new \BadMethodCallException('Encoder cannot be duplicated.');
    }
}
