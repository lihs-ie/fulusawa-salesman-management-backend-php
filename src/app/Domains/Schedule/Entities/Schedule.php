<?php

namespace App\Domains\Schedule\Entities;

use App\Domains\Common\ValueObjects\DateTimeRange;
use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\Domains\Schedule\ValueObjects\RepeatFrequency;
use App\Domains\Schedule\ValueObjects\ScheduleIdentifier;
use App\Domains\Schedule\ValueObjects\ScheduleStatus;
use App\Domains\User\ValueObjects\UserIdentifier;

/**
 * スケジュールを表すエンティティ
 */
class Schedule
{
    private const MAX_TITLE_LENGTH = 255;

    private const MAX_DESCRIPTION_LENGTH = 1000;

    public function __construct(
        public readonly ScheduleIdentifier $identifier,
        public readonly UserIdentifier $user,
        public readonly CustomerIdentifier|null $customer,
        public readonly string $title,
        public readonly string|null $description,
        public readonly DateTimeRange $date,
        public readonly ScheduleStatus $status,
        public readonly RepeatFrequency $repeat,
    ) {
        if (static::MAX_TITLE_LENGTH < mb_strlen($title)) {
            throw new \InvalidArgumentException(\sprintf(
                'Title must be less than or equal to %d characters.',
                static::MAX_TITLE_LENGTH
            ));
        }

        if (!is_null($description) && static::MAX_DESCRIPTION_LENGTH < mb_strlen($description)) {
            throw new \InvalidArgumentException(\sprintf(
                'Description must be less than or equal to %d characters.',
                static::MAX_DESCRIPTION_LENGTH
            ));
        }
    }

    public function identifier(): ScheduleIdentifier
    {
        return $this->identifier;
    }

    public function user(): UserIdentifier
    {
        return $this->user;
    }

    public function customer(): CustomerIdentifier|null
    {
        return $this->customer;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function description(): string|null
    {
        return $this->description;
    }

    public function date(): DateTimeRange
    {
        return $this->date;
    }

    public function status(): ScheduleStatus
    {
        return $this->status;
    }

    public function repeat(): RepeatFrequency
    {
        return $this->repeat;
    }

    /**
     * 他のスケジュールと同じか判定する
     *
     * @param Schedule $other
     * @return boolean
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function equals(Schedule $other): bool
    {
        if (!$this->identifier->equals($other->identifier)) {
            return false;
        }

        if (!$this->user->equals($other->user)) {
            return false;
        }

        if (\is_null($this->customer) && !\is_null($other->customer)) {
            return false;
        }

        if (!\is_null($this->customer) && \is_null($other->customer)) {
            return false;
        }

        if (!$this->customer->equals($other->customer)) {
            return false;
        }

        if ($this->title !== $other->title) {
            return false;
        }

        if ($this->description !== $other->description) {
            return false;
        }

        if (!$this->date->equals($other->date)) {
            return false;
        }

        if ($this->status !== $other->status) {
            return false;
        }

        if (!$this->repeat->equals($other->repeat)) {
            return false;
        }

        return true;
    }
}
