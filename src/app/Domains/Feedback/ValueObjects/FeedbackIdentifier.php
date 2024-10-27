<?php

namespace App\Domains\Feedback\ValueObjects;

use App\Domains\Common\ValueObjects\UniversallyUniqueIdentifier;

/**
 * フィードバック識別子を表す値オブジェクト
 */
class FeedbackIdentifier extends UniversallyUniqueIdentifier
{
    public function __construct(string $value)
    {
        parent::__construct($value);
    }
}
