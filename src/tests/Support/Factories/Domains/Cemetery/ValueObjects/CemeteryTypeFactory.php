<?php

namespace Tests\Support\Factories\Domains\Cemetery\ValueObjects;

use App\Domains\Cemetery\ValueObjects\CemeteryType;
use Tests\Support\Factories\EnumFactory;

/**
 * テスト用の墓地種別を生成するファクトリ.
 */
class CemeteryTypeFactory extends EnumFactory
{
    /**
     * {@inheritdoc}
     */
    protected function target(): string
    {
        return CemeteryType::class;
    }
}
