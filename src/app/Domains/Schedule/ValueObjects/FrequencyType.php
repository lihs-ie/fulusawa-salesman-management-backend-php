<?php

namespace App\Domains\Schedule\ValueObjects;

/**
 * スケジュールの繰り返しタイプを表す値オブジェクト
 */
enum FrequencyType
{
    case DAILY;
    case WEEKLY;
    case MONTHLY;
    case YEARLY;
}
