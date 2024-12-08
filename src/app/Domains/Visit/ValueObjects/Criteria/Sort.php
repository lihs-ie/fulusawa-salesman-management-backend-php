<?php

namespace App\Domains\Visit\ValueObjects\Criteria;

/**
 * ソート条件を表す値オブジェクト.
 */
enum Sort
{
    case VISITED_AT_ASC;

    case VISITED_AT_DESC;
}
