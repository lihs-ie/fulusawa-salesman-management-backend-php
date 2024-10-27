<?php

namespace App\Domains\Feedback\ValueObjects;

/**
 * フィードバックタイプを表す値オブジェクト
 */
enum FeedbackType
{
    case IMPROVEMENT;

    case PROBLEM;

    case OTHER;
}
