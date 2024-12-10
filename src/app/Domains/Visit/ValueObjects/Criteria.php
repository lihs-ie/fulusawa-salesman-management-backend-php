<?php

namespace App\Domains\Visit\ValueObjects;

use App\Domains\User\ValueObjects\UserIdentifier;
use App\Domains\Visit\ValueObjects\Criteria\Sort;

/**
 * 検索条件.
 */
class Criteria
{
    /**
     * コンストラクタ.
     */
    public function __construct(
        private readonly ?UserIdentifier $user,
        private readonly ?Sort $sort
    ) {}

    /**
     * ユーザー識別子を取得する.
     */
    public function user(): ?UserIdentifier
    {
        return $this->user;
    }

    /**
     * ソート条件を取得する.
     */
    public function sort(): ?Sort
    {
        return $this->sort;
    }

    /**
     * ハッシュ値を取得する.
     */
    public function hashCode(): string
    {
        $source = [
            'user' => $this->user->value(),
            'sort' => $this->sort->name,
        ];

        return \hash('sha256', \json_encode($source));
    }

    /**
     * 与えられた値が自身と同一か判定する.
     */
    public function equals(?self $comparison): bool
    {
        if (\is_null($comparison)) {
            return false;
        }

        return $this->hashCode() === $comparison->hashCode();
    }
}
