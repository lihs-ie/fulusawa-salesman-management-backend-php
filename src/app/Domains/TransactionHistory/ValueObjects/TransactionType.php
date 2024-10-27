<?php

namespace App\Domains\TransactionHistory\ValueObjects;

/**
 * 取引種別を表す値オブジェクト
 */
enum TransactionType
{
    case MAINTENANCE;

    case CLEANING;

    case GRAVESTONE_INSTALLATION;

    case GRAVESTONE_REMOVAL;

    case GRAVESTONE_REPLACEMENT;

    case GRAVESTONE_REPAIR;

    case OTHER;
}
