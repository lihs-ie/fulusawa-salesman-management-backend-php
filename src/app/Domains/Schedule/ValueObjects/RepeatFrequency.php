<?php

namespace App\Domains\Schedule\ValueObjects;

/**
 * スケジュールの繰り返し頻度を表す値オブジェクト
 */
class RepeatFrequency
{
    private const WEEK = 7;

    public function __construct(
        public readonly FrequencyType $type,
        public readonly int $interval,
    ) {
        if ($interval < 1) {
            throw new \InvalidArgumentException('Interval must be greater than 0');
        }
    }

    public function type(): FrequencyType
    {
        return $this->type;
    }

    public function interval(): int
    {
        return $this->interval;
    }

    public function equals(RepeatFrequency $other): bool
    {
        if ($this->type !== $other->type) {
            return false;
        }

        if ($this->interval !== $other->interval) {
            return false;
        }

        return true;
    }

    public function next(\DateTimeInterface $base): \DateTimeInterface
    {
        $next = clone $base;

        return match ($this->type) {
            FrequencyType::DAILY  => $next->modify(\sprintf('+%d days', $this->interval)),
            FrequencyType::WEEKLY => $next->modify(\sprintf('+%d days', ($this->interval * static::WEEK))),
            FrequencyType::MONTHLY => $next->modify(\sprintf('+%d months', $this->interval)),
            FrequencyType::YEARLY => $next->modify(\sprintf('+%d years', $this->interval)),
        };
    }
}
