<?php

namespace App\Domains\Common\ValueObjects;

/**
 * メールアドレスを表す値オブジェクト
 */
class MailAddress
{
    public const VALID_PATTERN = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';

    public function __construct(public readonly string $value)
    {
        if (!preg_match(self::VALID_PATTERN, $value)) {
            throw new \InvalidArgumentException('Value is not a valid mail address');
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(MailAddress $other): bool
    {
        return $this->value === $other->value;
    }
}
