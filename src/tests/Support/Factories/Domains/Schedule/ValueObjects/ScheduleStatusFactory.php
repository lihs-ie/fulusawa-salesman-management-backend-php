<?php

namespace Tests\Support\Factories\Domains\Schedule\ValueObjects;

use App\Domains\Schedule\ValueObjects\ScheduleStatus;
use Tests\Support\Factories\EnumFactory;

/**
 * テスト用のスケジュールステータスを生成するファクトリ.
 */
class ScheduleStatusFactory extends EnumFactory
{
    /**
     * {@inheritdoc}
     */
    protected function target(): string
    {
        return ScheduleStatus::class;
    }
}
