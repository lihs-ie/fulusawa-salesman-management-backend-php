<?php

namespace App\Domains\Schedule\ValueObjects;

use App\Domains\Common\ValueObjects\DateTimeRange;
use App\Domains\User\ValueObjects\UserIdentifier;

/**
 * スケジュールの検索条件を表す値オブジェクト
 */
class Criteria
{
    public function __construct(
        public readonly ScheduleStatus|null $status,
        public readonly DateTimeRange|null $date,
        public readonly string|null $title,
        public readonly UserIdentifier|null $user,
    ) {
        if (!\is_null($title) && \mb_strlen($title) === 0) {
            throw new \InvalidArgumentException('Title must not be empty.');
        }
    }

    public function status(): ScheduleStatus|null
    {
        return $this->status;
    }

    public function date(): DateTimeRange|null
    {
        return $this->date;
    }

    public function title(): string|null
    {
        return $this->title;
    }

    public function user(): UserIdentifier|null
    {
        return $this->user;
    }

    public function equals(?Criteria $other): bool
    {
        if (\is_null($other)) {
            return false;
        }

        if ($this->status !== $other->status) {
            return false;
        }

        if (!$this->date->equals($other->date)) {
            return false;
        }

        if ($this->title !== $other->title) {
            return false;
        }

        if (!$this->user->equals($other->user)) {
            return false;
        }

        return true;
    }
}
