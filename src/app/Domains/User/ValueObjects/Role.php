<?php

namespace App\Domains\User\ValueObjects;

/**
 * ユーザー権限を表す値オブジェクト
 */
enum Role
{
    case ADMIN;
    case USER;
}
