<?php

namespace App\Domains\Authentication\ValueObjects;

/**
 * トークンを表す値オブジェクト
 */
class Token
{
    public function __construct(
        public readonly TokenType $type,
        public readonly string $value,
        public readonly \DateTimeInterface $expiresAt,
    ) {
        if ($value === '') {
            throw new \InvalidArgumentException('Invalid value format');
        }
    }

    /**
     * トークン種別を取得する
     */
    public function type(): TokenType
    {
        return $this->type;
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

        if ($this->type !== $other->type) {
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
