<?php

namespace App\Domains\Feedback\ValueObjects;

use App\Domains\Feedback\ValueObjects\Criteria\Sort;

/**
 * フィードバックの検索条件を表す値オブジェクト
 */
class Criteria
{
    public function __construct(
        public readonly FeedbackStatus|null $status,
        public readonly FeedbackType|null $type,
        public readonly Sort|null $sort
    ) {
    }

    public function status(): FeedbackStatus|null
    {
        return $this->status;
    }

    public function type(): FeedbackType|null
    {
        return $this->type;
    }

    public function sort(): Sort|null
    {
        return $this->sort;
    }

    /**
     * 検索条件が等しいかどうかを判定する
     *
     * @param Criteria $criteria
     * @return boolean
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function equals(Criteria $other): bool
    {
        if (is_null($this->status) && !is_null($other->status)) {
            return false;
        }

        if ($this->status !== $other->status) {
            return false;
        }

        if (is_null($this->type) && !is_null($other->type)) {
            return false;
        }

        if ($this->type !== $other->type) {
            return false;
        }

        if (is_null($this->sort) && !is_null($other->sort)) {
            return false;
        }


        if ($this->sort !== $other->sort) {
            return false;
        }

        return true;
    }
}
