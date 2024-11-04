<?php

namespace App\Domains\Cemetery\ValueObjects;

/**
 * 墓地の種別を表す値オブジェクト
 */
enum CemeteryType
{
    case INDIVIDUAL;

    case FAMILY;

    case COMMUNITY;

    case OTHER;
}
