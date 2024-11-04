<?php

namespace Tests\Support\Factories\Domains\Schedule\ValueObjects;

use App\Domains\Schedule\ValueObjects\FrequencyType;
use Tests\Support\Factories\EnumFactory;

/**
 * テスト用の繰り返し種別を生成するファクトリ.
 */
class FrequencyTypeFactory extends EnumFactory
{
    /**
     * {@inheritdoc}
     */
    protected function target(): string
    {
        return FrequencyType::class;
    }
}
