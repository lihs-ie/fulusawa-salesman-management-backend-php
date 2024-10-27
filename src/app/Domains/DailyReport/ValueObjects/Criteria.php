<?php

namespace App\Domains\DailyReport\ValueObjects;

use App\Domains\Common\ValueObjects\DateTimeRange;
use App\Domains\User\ValueObjects\UserIdentifier;

/**
 * 日報検索条件を表す値オブジェクト
 */
class Criteria
{
    public function __construct(
        public readonly DateTimeRange|null $date,
        public readonly UserIdentifier|null $user,
        public readonly bool|null $isSubmitted,
    ) {
    }

    public function date(): DateTimeRange|null
    {
        return $this->date;
    }

    public function user(): UserIdentifier|null
    {
        return $this->user;
    }

    public function isSubmitted(): bool|null
    {
        return $this->isSubmitted;
    }

    /**
     * 検索条件が等しいかどうかを判定する
     *
     * @param Criteria $criteria
     * @return boolean
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function equals(Criteria $criteria): bool
    {
        if (\is_null($this->date) && !\is_null($criteria->date)) {
            return false;
        }

        if (!\is_null($this->date) && \is_null($criteria->date)) {
            return false;
        }

        if (!$this->date->equals($criteria->date)) {
            return false;
        }

        if (\is_null($this->user) && !\is_null($criteria->user)) {
            return false;
        }

        if (!\is_null($this->user) && \is_null($criteria->user)) {
            return false;
        }

        if (!$this->user->equals($criteria->user)) {
            return false;
        }

        if ($this->isSubmitted !== $criteria->isSubmitted) {
            return false;
        }

        return true;
    }
}
