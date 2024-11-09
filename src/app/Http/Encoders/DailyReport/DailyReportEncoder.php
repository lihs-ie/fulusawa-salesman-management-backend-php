<?php

namespace App\Http\Encoders\DailyReport;

use App\Domains\DailyReport\Entities\DailyReport;
use App\Domains\Schedule\ValueObjects\ScheduleIdentifier;
use App\Domains\Visit\ValueObjects\VisitIdentifier;

/**
 * 日報エンコーダ.
 */
class DailyReportEncoder
{
    /**
     * 日報をJSONエンコード可能な形式に変換する.
     */
    public function encode(DailyReport $dailyReport): array
    {
        return [
          'identifier' => $dailyReport->identifier()->value(),
          'user' => $dailyReport->user()->value(),
          'date' => $dailyReport->date()->toAtomString(),
          'schedules' => $dailyReport->schedules()
            ->map(fn (ScheduleIdentifier $schedule): string => $schedule->value())
            ->all(),
          'visits' => $dailyReport->visits()
            ->map(fn (VisitIdentifier $visit): string => $visit->value())
            ->all(),
          'isSubmitted' => $dailyReport->isSubmitted(),
        ];
    }
}
