<?php

namespace App\Domains\DailyReport\ValueObjects;

use App\Domains\Common\ValueObjects\UniversallyUniqueIdentifier;

/**
 * 日報識別子を表す値オブジェクト
 */
class DailyReportIdentifier extends UniversallyUniqueIdentifier
{
    public function __construct(string $value)
    {
        parent::__construct($value);
    }
}
