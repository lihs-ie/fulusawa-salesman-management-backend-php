<?php

namespace App\Domains\Common\ValueObjects;

/**
 * 郵便番号を表す値オブジェクト
 */
class PostalCode
{
    public const VALID_FIRST_PATTERN = '/^\d{3}$/';

    public const VALID_SECOND_PATTERN = '/^\d{4}$/';

    public function __construct(public readonly string $first, public readonly string $second)
    {
        if (!preg_match(self::VALID_FIRST_PATTERN, $first) || !preg_match(self::VALID_SECOND_PATTERN, $second)) {
            throw new \InvalidArgumentException('Value is not a valid postal code');
        }
    }

    public function first(): string
    {
        return $this->first;
    }

    public function second(): string
    {
        return $this->second;
    }

    public function __toString(): string
    {
        return \sprintf('%s-%s', $this->first, $this->second);
    }

    public function equals(PostalCode $other): bool
    {
        return $this->first === $other->first && $this->second === $other->second;
    }
}
