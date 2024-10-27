<?php

namespace App\Domains\Visit\ValueObjects;

/**
 * 訪問結果を表す値オブジェクト
 */
enum VisitResult
{
    case CONTRACT;

    case NO_CONTRACT;
}
