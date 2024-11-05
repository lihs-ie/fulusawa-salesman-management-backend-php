<?php

namespace App\Domains\Customer\Entities;

use App\Domains\Cemetery\ValueObjects\CemeteryIdentifier;
use App\Domains\Common\ValueObjects\Address;
use App\Domains\Common\ValueObjects\PhoneNumber;
use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\Domains\TransactionHistory\ValueObjects\TransactionHistoryIdentifier;
use Illuminate\Support\Enumerable;

/**
 * 顧客エンティティ
 */
class Customer
{
    private const MAX_LAST_NAME_LENGTH = 50;

    private const MAX_FIRST_NAME_LENGTH = 50;

    public function __construct(
        public readonly CustomerIdentifier $identifier,
        public readonly string $lastName,
        public readonly string|null $firstName,
        public readonly Address $address,
        public readonly PhoneNumber $phone,
        public readonly Enumerable $cemeteries,
        public readonly Enumerable $transactionHistories,
    ) {
        if ($lastName === '' || static::MAX_LAST_NAME_LENGTH < mb_strlen($lastName)) {
            throw new \InvalidArgumentException(\sprintf(
                'Last name must be less than or equal to %d characters.',
                static::MAX_LAST_NAME_LENGTH
            ));
        }

        if (!is_null($firstName) && $firstName === '' && static::MAX_FIRST_NAME_LENGTH < mb_strlen($firstName)) {
            throw new \InvalidArgumentException(\sprintf(
                'First name must be less than or equal to %d characters.',
                static::MAX_FIRST_NAME_LENGTH
            ));
        }

        $cemeteries->each(function ($cemetery): void {
            if (!$cemetery instanceof CemeteryIdentifier) {
                throw new \InvalidArgumentException('Cemeteries contains invalid value.');
            }
        });

        $transactionHistories->each(function ($transactionHistory): void {
            if (!$transactionHistory instanceof TransactionHistoryIdentifier) {
                throw new \InvalidArgumentException('Transaction histories contains invalid value.');
            }
        });
    }

    public function identifier(): CustomerIdentifier
    {
        return $this->identifier;
    }

    public function lastName(): string
    {
        return $this->lastName;
    }

    public function firstName(): string|null
    {
        return $this->firstName;
    }

    public function address(): Address
    {
        return $this->address;
    }

    public function phone(): PhoneNumber
    {
        return $this->phone;
    }

    public function cemeteries(): Enumerable
    {
        return $this->cemeteries;
    }

    public function transactionHistories(): Enumerable
    {
        return $this->transactionHistories;
    }

    /**
     * 与えられた値が自信と同一か判定する
     *
     * @param Customer|null $other
     * @return boolean
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function equals(?Customer $other): bool
    {
        if (is_null($other)) {
            return false;
        }

        if (!$this->identifier->equals($other->identifier)) {
            return false;
        }

        if ($this->lastName !== $other->lastName) {
            return false;
        }

        if ($this->firstName !== $other->firstName) {
            return false;
        }

        if (!$this->address->equals($other->address)) {
            return false;
        }

        if (!$this->phone->equals($other->phone)) {
            return false;
        }

        if (!$this->isSameCemeteries($other->cemeteries)) {
            return false;
        }

        if (!$this->isSameTransactionHistories($other->transactionHistories)) {
            return false;
        }

        return true;
    }

    private function isSameCemeteries(Enumerable $other): bool
    {
        if ($this->cemeteries->count() !== $other->count()) {
            return false;
        }

        $valids = $this->cemeteries->each(function ($cemetery) use ($other): bool {
            if (!$other->contains($cemetery)) {
                return false;
            }

            return true;
        });

        return !$valids->has(false);
    }

    private function isSameTransactionHistories(Enumerable $other): bool
    {
        if ($this->transactionHistories->count() !== $other->count()) {
            return false;
        }

        $valids = $this->transactionHistories->each(function ($transactionHistory) use ($other): bool {
            if (!$other->contains($transactionHistory)) {
                return false;
            }

            return true;
        });

        return !$valids->has(false);
    }
}
