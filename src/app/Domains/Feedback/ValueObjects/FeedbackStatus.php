<?php

namespace App\Domains\Feedback\ValueObjects;

/**
 * フィードバックステータスを表す値オブジェクト
 */
enum FeedbackStatus
{
    case WAITING;

    case IN_PROGRESS;

    case COMPLETED;

    case NOT_NECESSARY;
}
