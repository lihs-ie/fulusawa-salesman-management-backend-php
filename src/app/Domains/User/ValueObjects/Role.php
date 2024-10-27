<?php

namespace App\Domains\User\ValueObjects;

/**
 * ユーザーの権限を表す値オブジェクト
 */
enum Role
{
    case ADMIN;
    case USER;
}
