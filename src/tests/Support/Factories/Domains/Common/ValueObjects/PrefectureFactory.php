<?php

namespace Tests\Support\Factories\Domains\Common\ValueObjects;

use App\Domains\Common\ValueObjects\Prefecture;
use Tests\Support\Factories\EnumFactory;

/**
 * テスト用の都道府県を生成するファクトリ.
 */
class PrefectureFactory extends EnumFactory
{
    /**
     * {@inheritdoc}
     */
    protected function target(): string
    {
        return Prefecture::class;
    }
}
