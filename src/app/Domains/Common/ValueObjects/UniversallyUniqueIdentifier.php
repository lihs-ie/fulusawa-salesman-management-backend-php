<?php

namespace App\Domains\Common\ValueObjects;

/**
 * UUID形式の識別子を表値オブジェクト
 */
abstract class UniversallyUniqueIdentifier
{
    public const VALID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

    public function __construct(public readonly string $value)
    {

        if (!preg_match(self::VALID_PATTERN, $value)) {
            throw new \InvalidArgumentException('Value is not a valid UUID');
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(UniversallyUniqueIdentifier $other): bool
    {
        return $this->value === $other->value;
    }
}
