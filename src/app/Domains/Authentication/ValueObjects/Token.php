<?php

namespace App\Domains\Authentication\ValueObjects;

/**
 * トークンを表す値オブジェクト
 */
class Token
{
    public const VALID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\|.{1,}$/';

    public function __construct(
        public readonly string $value,
        public readonly \DateTimeInterface $expiresAt,
    ) {
        if (!\preg_match(self::VALID_PATTERN, $value)) {
            throw new \InvalidArgumentException('Invalid value format');
        }
    }

    /**
     * 値を取得する
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * 有効期限を取得する
     */
    public function expiresAt(): \DateTimeInterface
    {
        return $this->expiresAt;
    }

    /**
     * 自身の値を|で分割する
     */
    public function split(): array
    {
        return \explode('|', $this->value);
    }

    /**
     * 与えられた値が自身と等しいか判定する
     */
    public function equals(?self $other): bool
    {
        if (is_null($other)) {
            return false;
        }

        if ($this->value !== $other->value) {
            return false;
        }

        if ($this->expiresAt->toAtomString() !== $other->expiresAt->toAtomString()) {
            return false;
        }

        return true;
    }
}
