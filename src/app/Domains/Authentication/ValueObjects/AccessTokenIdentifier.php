<?php

namespace App\Domains\Authentication\ValueObjects;

/**
 * 認証識別子を表す値オブジェクト
 */
class AccessTokenIdentifier
{
    public function __construct(
        private readonly string $value
    ) {
        if (\mb_strlen($value) < 1) {
            throw new \InvalidArgumentException('Value must be at least 1 character');
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
     * 自身の値を|で分割する
     */
    public function split(): array
    {
        return \explode('|', $this->value);
    }
}
