<?php

namespace Tests\Support\Factories\Domains\Cemetery\ValueObjects;

use App\Domains\Cemetery\ValueObjects\CemeteryIdentifier;
use Tests\Support\Factories\Domains\Common\ValueObjects\UniversallyUniqueIdentifierFactory;

/**
 * テスト用の墓地情報識別子を生成するファクトリ.
 */
class CemeteryIdentifierFactory extends UniversallyUniqueIdentifierFactory
{
    /**
     * {@inheritdoc}
     */
    protected function target(): string
    {
        return CemeteryIdentifier::class;
    }
}
