<?php

namespace App\Domains\Schedule\ValueObjects;

/**
 * スケジュールのステータスを表す値オブジェクト
 */
enum ScheduleStatus
{
    case IN_COMPLETE;

    case IN_PROGRESS;

    case COMPLETED;
}
