<?php

namespace App\Domains\Cemetery\Entities;

use App\Domains\Cemetery\ValueObjects\CemeteryIdentifier;
use App\Domains\Cemetery\ValueObjects\CemeteryType;
use App\Domains\Customer\ValueObjects\CustomerIdentifier;

/**
 * 墓地情報を表すエンティティ
 */
class Cemetery
{
    public function __construct(
        public readonly CemeteryIdentifier $identifier,
        public readonly CustomerIdentifier $customer,
        public readonly string $name,
        public readonly CemeteryType $type,
        public readonly \DateTimeInterface $construction,
        public readonly bool $inHouse,
    ) {
        if ($name === '') {
            throw new \InvalidArgumentException('Name must not be empty.');
        }
    }

    public function identifier(): CemeteryIdentifier
    {
        return $this->identifier;
    }

    public function customer(): CustomerIdentifier
    {
        return $this->customer;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function type(): CemeteryType
    {
        return $this->type;
    }

    public function construction(): \DateTimeInterface
    {
        return $this->construction;
    }

    public function inHouse(): bool
    {
        return $this->inHouse;
    }

    public function equals(Cemetery $other): bool
    {
        if (!$this->identifier->equals($other->identifier)) {
            return false;
        }

        if (!$this->customer->equals($other->customer)) {
            return false;
        }

        if ($this->name !== $other->name) {
            return false;
        }

        if ($this->type !== $other->type) {
            return false;
        }

        if ($this->construction->format('Y-m-d') !== $other->construction->format('Y-m-d')) {
            return false;
        }

        if ($this->inHouse !== $other->inHouse) {
            return false;
        }

        return true;
    }
}
