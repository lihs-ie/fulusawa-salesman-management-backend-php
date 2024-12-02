<?php

namespace App\Domains\DailyReport\Entities;

use App\Domains\DailyReport\ValueObjects\DailyReportIdentifier;
use App\Domains\Schedule\ValueObjects\ScheduleIdentifier;
use App\Domains\User\ValueObjects\UserIdentifier;
use App\Domains\Visit\ValueObjects\VisitIdentifier;
use Carbon\CarbonImmutable;
use Illuminate\Support\Enumerable;

/**
 * 日報を表すエンティティ
 */
class DailyReport
{
    public function __construct(
        public readonly DailyReportIdentifier $identifier,
        public readonly UserIdentifier $user,
        public readonly \DateTimeImmutable $date,
        public readonly Enumerable $schedules,
        public readonly Enumerable $visits,
        public readonly bool $isSubmitted,
    ) {
        if (CarbonImmutable::now()->isBefore($date)) {
            throw new \InvalidArgumentException('Date must be in the past');
        }

        $schedules->each(function ($schedule): void {
            if (!$schedule instanceof ScheduleIdentifier) {
                throw new \InvalidArgumentException('Schedules must be an instance of ScheduleIdentifier');
            }
        });

        $visits->each(function ($visit): void {
            if (!$visit instanceof VisitIdentifier) {
                throw new \InvalidArgumentException('Visits must be an instance of VisitIdentifier');
            }
        });
    }

    public function identifier(): DailyReportIdentifier
    {
        return $this->identifier;
    }

    public function user(): UserIdentifier
    {
        return $this->user;
    }

    public function date(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function schedules(): Enumerable
    {
        return $this->schedules;
    }

    public function visits(): Enumerable
    {
        return $this->visits;
    }

    public function isSubmitted(): bool
    {
        return $this->isSubmitted;
    }

    /**
     * 他の日報と同じかどうかを判定する
     *
     * @param DailyReport $other
     * @return boolean
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function equals(DailyReport $other): bool
    {
        if (!$this->identifier->equals($other->identifier)) {
            return false;
        }

        if (!$this->user->equals($other->user)) {
            return false;
        }

        if ($this->date != $other->date) {
            return false;
        }

        if (!$this->isSameSchedules($other->schedules)) {
            return false;
        }

        if (!$this->isSameVisits($other->visits)) {
            return false;
        }

        if ($this->isSubmitted !== $other->isSubmitted) {
            return false;
        }

        return true;
    }

    private function isSameSchedules(Enumerable $other): bool
    {
        return $this->schedules->diff($other)->isEmpty();
    }

    private function isSameVisits(Enumerable $other): bool
    {
        return $this->visits->diff($other)->isEmpty();
    }
}
