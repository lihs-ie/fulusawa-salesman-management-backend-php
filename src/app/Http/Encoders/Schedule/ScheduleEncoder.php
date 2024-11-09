<?php

namespace App\Http\Encoders\Schedule;

use App\Domains\Schedule\Entities\Schedule;

/**
 * スケジュールエンコーダ.
 */
class ScheduleEncoder
{
    /**
     * スケジュールをJSONエンコード可能な形式に変換する.
     */
    public function encode(Schedule $schedule): array
    {
        return [
          'identifier' => $schedule->identifier()->value(),
          'user' => $schedule->user()->value(),
          'customer' => $schedule->customer()?->value(),
          'title' => $schedule->title(),
          'description' => $schedule->description(),
          'date' => [
            'start' => $schedule->date()->start()->toAtomString(),
            'end' => $schedule->date()->end()->toAtomString()
          ],
          'status' => $schedule->status()->name,
          'repeatFrequency' => \is_null($schedule->repeat()) ? null : [
            'type' => $schedule->repeat()->type()->name,
            'interval' => $schedule->repeat()->interval()
          ]
        ];
    }
}
