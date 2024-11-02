<?php

namespace Tests\Support\Factories\Domains\Visit\ValueObjects;

use App\Domains\Visit\ValueObjects\VisitIdentifier;
use Tests\Support\Factories\Domains\Common\ValueObjects\UniversallyUniqueIdentifierFactory;

/**
 * テスト用の訪問識別子を生成するファクトリ.
 */
class VisitIdentifierFactory extends UniversallyUniqueIdentifierFactory
{
    /**
     * {@inheritdoc}
     */
    protected function target(): string
    {
        return VisitIdentifier::class;
    }
}
