<?php

namespace App\Domains\Schedule\ValueObjects;

use App\Domains\Common\ValueObjects\UniversallyUniqueIdentifier;

/**
 * スケジュール識別子を表す値オブジェクト
 */
class ScheduleIdentifier extends UniversallyUniqueIdentifier
{
    public function __construct(string $value)
    {
        parent::__construct($value);
    }
}
