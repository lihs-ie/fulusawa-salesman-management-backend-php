<?php

namespace App\Domains\TransactionHistory\Entities;

use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\Domains\TransactionHistory\ValueObjects\TransactionHistoryIdentifier;
use App\Domains\TransactionHistory\ValueObjects\TransactionType;
use App\Domains\User\ValueObjects\UserIdentifier;
use Carbon\CarbonImmutable;

/**
 * 取引履歴エンティティ
 */
class TransactionHistory
{
    private const MAX_DESCRIPTION_LENGTH = 1000;

    public function __construct(
        public readonly TransactionHistoryIdentifier $identifier,
        public readonly CustomerIdentifier $customer,
        public readonly UserIdentifier $salesman,
        public readonly TransactionType $type,
        public readonly string|null $description,
        public readonly \DateTimeInterface $date,
    ) {
        if (!is_null($description) && $description === '' && static::MAX_DESCRIPTION_LENGTH < mb_strlen($description)) {
            throw new \InvalidArgumentException(\sprintf(
                'Description must be less than or equal to %d characters.',
                static::MAX_DESCRIPTION_LENGTH
            ));
        }

        if ($date > CarbonImmutable::now()) {
            throw new \InvalidArgumentException('Date must not be in the future.');
        }
    }

    public function identifier(): TransactionHistoryIdentifier
    {
        return $this->identifier;
    }

    public function customer(): CustomerIdentifier
    {
        return $this->customer;
    }

    public function salesman(): UserIdentifier
    {
        return $this->salesman;
    }

    public function type(): TransactionType
    {
        return $this->type;
    }

    public function description(): string|null
    {
        return $this->description;
    }

    public function date(): \DateTimeInterface
    {
        return $this->date;
    }

    public function equals(TransactionHistory $other): bool
    {
        if (!$this->identifier->equals($other->identifier)) {
            return false;
        }

        if (!$this->customer->equals($other->customer)) {
            return false;
        }

        if (!$this->salesman->equals($other->salesman)) {
            return false;
        }

        if ($this->type !== $other->type) {
            return false;
        }

        if ($this->description !== $other->description) {
            return false;
        }

        if ($this->date->format('Y-m-d H:i:s') !== $other->date->format('Y-m-d H:i:s')) {
            return false;
        }

        return true;
    }
}
