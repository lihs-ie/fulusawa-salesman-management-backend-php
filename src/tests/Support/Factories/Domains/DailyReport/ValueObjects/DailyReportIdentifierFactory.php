<?php

namespace Tests\Support\Factories\Domains\DailyReport\ValueObjects;

use App\Domains\DailyReport\ValueObjects\DailyReportIdentifier;
use Tests\Support\Factories\Domains\Common\ValueObjects\UniversallyUniqueIdentifierFactory;

/**
 * テスト用の日報識別子を生成するファクトリ.
 */
class DailyReportIdentifierFactory extends UniversallyUniqueIdentifierFactory
{
    /**
     * {@inheritdoc}
     */
    protected function target(): string
    {
        return DailyReportIdentifier::class;
    }
}
