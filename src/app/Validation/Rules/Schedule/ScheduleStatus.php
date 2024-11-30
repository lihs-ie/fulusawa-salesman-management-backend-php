<?php

namespace App\Validation\Rules\Schedule;

use App\Validation\Rules\EnumRule;

/**
 * スケジュールステータスバリデーションルール.
 */
class ScheduleStatus extends EnumRule
{
    /**
     * {@inheritdoc}
     */
    protected function source(): string
    {
        return \App\Domains\Schedule\ValueObjects\ScheduleStatus::class;
    }
}
