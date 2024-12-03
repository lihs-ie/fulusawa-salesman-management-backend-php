<?php

namespace App\Domains\Schedule\Entities;

use App\Domains\Common\Utils\CollectionUtil;
use App\Domains\Common\ValueObjects\DateTimeRange;
use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\Domains\Schedule\ValueObjects\RepeatFrequency;
use App\Domains\Schedule\ValueObjects\ScheduleContent;
use App\Domains\Schedule\ValueObjects\ScheduleIdentifier;
use App\Domains\Schedule\ValueObjects\ScheduleStatus;
use App\Domains\User\ValueObjects\UserIdentifier;
use Illuminate\Support\Enumerable;

/**
 * スケジュールを表すエンティティ
 */
class Schedule
{
    /**
     * 参加者の最大数.
     */
    public const MAX_PARTICIPANTS = 10;

    /**
     * コンストラクタ.
     *
     * @param ScheduleIdentifier $identifier
     * @param Enumerable $participants
     * @param UserIdentifier $creator
     * @param UserIdentifier $updater
     * @param CustomerIdentifier|null $customer
     * @param ScheduleContent $content
     * @param DateTimeRange $date
     * @param ScheduleStatus $status
     * @param RepeatFrequency|null $repeat
     */
    public function __construct(
        public readonly ScheduleIdentifier $identifier,
        public readonly Enumerable $participants,
        public readonly UserIdentifier $creator,
        public readonly UserIdentifier $updater,
        public readonly CustomerIdentifier|null $customer,
        public readonly ScheduleContent $content,
        public readonly DateTimeRange $date,
        public readonly ScheduleStatus $status,
        public readonly RepeatFrequency|null $repeat,
    ) {
        if ($participants->isEmpty()) {
            throw new \InvalidArgumentException('Participants must not be empty.');
        }

        $participants->each(function ($participant) {
            if (!($participant instanceof UserIdentifier)) {
                throw new \InvalidArgumentException('Participants must be UserIdentifier.');
            }
        });

        if (static::MAX_PARTICIPANTS < $participants->count()) {
            throw new \InvalidArgumentException(\sprintf('Participants must be less than or equal to %d.', static::MAX_PARTICIPANTS));
        }
    }

    public function identifier(): ScheduleIdentifier
    {
        return $this->identifier;
    }

    public function participants(): Enumerable
    {
        return $this->participants;
    }

    public function creator(): UserIdentifier
    {
        return $this->creator;
    }

    public function updater(): UserIdentifier
    {
        return $this->updater;
    }

    public function customer(): CustomerIdentifier|null
    {
        return $this->customer;
    }

    public function content(): ScheduleContent
    {
        return $this->content;
    }

    public function date(): DateTimeRange
    {
        return $this->date;
    }

    public function status(): ScheduleStatus
    {
        return $this->status;
    }

    public function repeat(): RepeatFrequency|null
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function equals(Schedule $other): bool
    {
        if (!$this->identifier->equals($other->identifier)) {
            return false;
        }

        if (!CollectionUtil::equalsAsSet(
            $this->participants,
            $other->participants,
            fn (UserIdentifier $left, UserIdentifier $right) => $left->equals($right)
        )) {
            return false;
        }

        if (!$this->creator->equals($other->creator)) {
            return false;
        }

        if (!$this->updater->equals($other->updater)) {
            return false;
        }

        if (\is_null($this->customer) && !\is_null($other->customer)) {
            return false;
        }

        if (!$this->customer->equals($other->customer)) {
            return false;
        }

        if (!$this->content->equals($other->content)) {
            return false;
        }

        if (!$this->date->equals($other->date)) {
            return false;
        }

        if ($this->status !== $other->status) {
            return false;
        }

        if (\is_null($this->repeat) && !\is_null($other->repeat)) {
            return false;
        }

        if (!$this->repeat->equals($other->repeat)) {
            return false;
        }

        return true;
    }
}
