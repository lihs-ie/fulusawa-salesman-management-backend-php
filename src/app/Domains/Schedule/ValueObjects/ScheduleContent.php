<?php

namespace App\Domains\Schedule\ValueObjects;

/**
 * スケジュール内容を表す値オブジェクト
 */
class ScheduleContent
{
    /**
     * タイトルの最大文字長.
     */
    public const MAX_TITLE_LENGTH = 255;

    /**
     * 説明の最大文字長.
     */
    public const MAX_DESCRIPTION_LENGTH = 1000;

    /**
     * コンストラクタ.
     */
    public function __construct(
        public readonly string $title,
        public readonly string|null $description,
    ) {
        if ($title === '' || static::MAX_TITLE_LENGTH < mb_strlen($title)) {
            throw new \InvalidArgumentException(\sprintf(
                'Title must be less than or equal to %d characters.',
                static::MAX_TITLE_LENGTH
            ));
        }

        if (!\is_null($description) && static::MAX_DESCRIPTION_LENGTH < mb_strlen($description)) {
            throw new \InvalidArgumentException(\sprintf(
                'Description must be less than or equal to %d characters.',
                static::MAX_DESCRIPTION_LENGTH
            ));
        }
    }

    /**
     * タイトルを取得する
     */
    public function title(): string
    {
        return $this->title;
    }

    /**
     * 説明を取得する
     */
    public function description(): string|null
    {
        return $this->description;
    }

    /**
     * 与えられた値が自信と同一か判定する.
     */
    public function equals(?self $comparison): bool
    {
        if (\is_null($comparison)) {
            return false;
        }

        if ($this->title !== $comparison->title) {
            return false;
        }

        if ($this->description !== $comparison->description) {
            return false;
        }

        return true;
    }
}
