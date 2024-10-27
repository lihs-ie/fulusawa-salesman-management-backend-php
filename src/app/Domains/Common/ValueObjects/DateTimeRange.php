<?php

namespace App\Domains\Common\ValueObjects;

/**
 * 日時範囲.
 */
class DateTimeRange
{
    /**
     * コンストラクタ.
     */
    public function __construct(
        private \DateTimeInterface|null $start,
        private \DateTimeInterface|null $end
    ) {
        if (!\is_null($start) && !\is_null($end)) {
            if ($end < $start) {
                throw new \InvalidArgumentException('End date must be after start date.');
            }
        }
    }

    /**
     * 開始日時を取得する.
     */
    public function start(): ?\DateTimeInterface
    {
        return $this->start;
    }

    /**
     * 終了日時を取得する.
     */
    public function end(): ?\DateTimeInterface
    {
        return $this->end;
    }

    /**
     * 指定した日時が範囲に含まれているか判定する.
     */
    public function includes(\DateTimeInterface $needle): bool
    {
        if ($this->isGreaterThan($needle)) {
            return false;
        }

        if ($this->isLessThan($needle)) {
            return false;
        }

        return true;
    }

    /**
     * 指定した日時が開始日時より前か判定する.
     */
    public function isGreaterThan(\DateTimeInterface $needle): bool
    {
        if (\is_null($this->start())) {
            return false;
        }

        if ($this->start() <= $needle) {
            return false;
        }

        return true;
    }

    /**
     * 指定した日時が終了日時より後か判定する.
     */
    public function isLessThan(\DateTimeInterface $needle): bool
    {
        if (\is_null($this->end())) {
            return false;
        }

        if ($needle <= $this->end()) {
            return false;
        }

        return true;
    }

    /**
     * 指定した日時が自身と同一か判定する.
     */
    public function equals(?self $comparand): bool
    {
        if (\is_null($comparand)) {
            return false;
        }

        if (!$this->compareBorder($this->start(), $comparand->start())) {
            return false;
        }

        if (!$this->compareBorder($this->end(), $comparand->end())) {
            return false;
        }

        return true;
    }

    /**
     * 指定した日時型の値が同一か判定する.
     */
    private function compareBorder(?\DateTimeInterface $own, ?\DateTimeInterface $foreign): bool
    {
        if (\is_null($own)) {
            if (!\is_null($foreign)) {
                return false;
            }
        } else {
            if (\is_null($foreign)) {
                return false;
            }

            if ($own->toAtomString() !== $foreign->toAtomString()) {
                return false;
            }
        }

        return true;
    }
}
