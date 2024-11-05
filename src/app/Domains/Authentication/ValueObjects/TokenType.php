<?php

namespace App\Domains\Authentication\ValueObjects;

/**
 * トークンの種別を表す値オブジェクト
 */
enum TokenType
{
    case ACCESS;

    case REFRESH;
}
