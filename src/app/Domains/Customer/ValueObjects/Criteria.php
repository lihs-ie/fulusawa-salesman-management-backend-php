<?php

namespace App\Domains\Customer\ValueObjects;

use App\Domains\Common\ValueObjects\PhoneNumber;
use App\Domains\Common\ValueObjects\PostalCode;

/**
 * 検索条件.
 */
class Criteria
{
    /**
     * 名前の最大文字長.
     */
    public const int NAME_MAX_LENGTH = 100;

    /**
     * コンストラクタ.
     */
    public function __construct(
        public readonly string|null $name,
        public readonly PostalCode|null $postalCode,
        public readonly PhoneNumber|null $phone
    ) {
        if (!is_null($name)) {
            if ($name === '' || static::NAME_MAX_LENGTH < mb_strlen($name)) {
                throw new \InvalidArgumentException(
                    \sprintf('Name must be between 1 and %d characters.', static::NAME_MAX_LENGTH)
                );
            }
        }
    }

    /**
     * 名前を取得する.
     */
    public function name(): string|null
    {
        return $this->name;
    }

    /**
     * 郵便番号を取得する.
     */

    public function postalCode(): PostalCode|null
    {
        return $this->postalCode;
    }

    /**
     * 電話番号を取得する.
     */
    public function phone(): PhoneNumber|null
    {
        return $this->phone;
    }

    /**
     * 与えられた値が自身と同一か判定する.
     */
    public function equals(?Criteria $other): bool
    {
        if (is_null($other)) {
            return false;
        }

        if ($this->name !== $other->name) {
            return false;
        }

        if (!$this->postalCode->equals($other->postalCode)) {
            return false;
        }

        if (!$this->phone->equals($other->phone)) {
            return false;
        }

        return true;
    }
}
