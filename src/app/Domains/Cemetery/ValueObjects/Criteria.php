<?php

namespace App\Domains\Cemetery\ValueObjects;

use App\Domains\Customer\ValueObjects\CustomerIdentifier;

/**
 * 検索条件
 */
class Criteria
{
    public function __construct(
        public readonly CustomerIdentifier|null $customer,
    ) {
    }

    /**
     * 与えられた値が自信と同一かどうかを判定する
     */
    public function equals(?self $comparison): bool
    {
        if (\is_null($comparison)) {
            return false;
        }

        if (!$this->customer->equals($comparison->customer)) {
            return false;
        }

        return true;
    }
}
