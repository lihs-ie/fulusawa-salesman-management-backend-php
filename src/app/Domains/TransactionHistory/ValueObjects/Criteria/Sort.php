<?php

namespace App\Domains\TransactionHistory\ValueObjects\Criteria;

/**
 * ソート条件を表す値オブジェクト
 */
enum Sort
{
  case CREATED_AT_DESC;

  case CREATED_AT_ASC;

  case UPDATED_AT_DESC;

  case UPDATED_AT_ASC;
}
