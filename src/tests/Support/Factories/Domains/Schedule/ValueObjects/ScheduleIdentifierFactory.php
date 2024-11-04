<?php

namespace Tests\Support\Factories\Domains\Schedule\ValueObjects;

use App\Domains\Schedule\ValueObjects\ScheduleIdentifier;
use Tests\Support\Factories\Domains\Common\ValueObjects\UniversallyUniqueIdentifierFactory;

/**
 * テスト用のスケジュール識別子を生成するファクトリ.
 */
class ScheduleIdentifierFactory extends UniversallyUniqueIdentifierFactory
{
    /**
     * {@inheritdoc}
     */
    protected function target(): string
    {
        return ScheduleIdentifier::class;
    }
}
