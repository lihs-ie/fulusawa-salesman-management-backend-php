<?php

namespace Tests\Support\Factories\Domains\Visit\ValueObjects;

use App\Domains\Visit\ValueObjects\VisitResult;
use Tests\Support\Factories\EnumFactory;

/**
 * テスト用の訪問結果を生成するファクトリ.
 */
class VisitResultFactory extends EnumFactory
{
    /**
     * {@inheritdoc}
     */
    protected function target(): string
    {
        return VisitResult::class;
    }
}
