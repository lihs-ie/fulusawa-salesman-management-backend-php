<?php

namespace App\Domains\Visit\Entities;

use App\Domains\Common\ValueObjects\Address;
use App\Domains\Common\ValueObjects\PhoneNumber;
use App\Domains\User\ValueObjects\UserIdentifier;
use App\Domains\Visit\ValueObjects\VisitIdentifier;
use App\Domains\Visit\ValueObjects\VisitResult;
use Carbon\CarbonImmutable;

/**
 * 訪問エンティティ
 */
class Visit
{
    private const MAX_NOTE_LENGTH = 1000;

    public function __construct(
        public readonly VisitIdentifier $identifier,
        public readonly UserIdentifier $user,
        public readonly \DateTimeInterface $visitedAt,
        public readonly Address $address,
        public readonly PhoneNumber|null $phone,
        public readonly bool $hasGraveyard,
        public readonly string|null $note,
        public readonly VisitResult $result
    ) {
        if (CarbonImmutable::now() < $this->visitedAt) {
            throw new \InvalidArgumentException('Visit date must be in the past');
        }

        if ($result === VisitResult::CONTRACT && \is_null($phone)) {
            throw new \InvalidArgumentException('Phone number must be set when the result is contract');
        }

        if (!\is_null($note) && static::MAX_NOTE_LENGTH < mb_strlen($note)) {
            throw new \InvalidArgumentException(\sprintf(
                'Note must be less than or equal to %d characters.',
                static::MAX_NOTE_LENGTH
            ));
        }
    }

    public function identifier(): VisitIdentifier
    {
        return $this->identifier;
    }

    public function user(): UserIdentifier
    {
        return $this->user;
    }

    public function visitedAt(): \DateTimeInterface
    {
        return $this->visitedAt;
    }

    public function address(): Address
    {
        return $this->address;
    }

    public function phone(): PhoneNumber|null
    {
        return $this->phone;
    }

    public function hasGraveyard(): bool
    {
        return $this->hasGraveyard;
    }

    public function note(): string|null
    {
        return $this->note;
    }

    public function result(): VisitResult
    {
        return $this->result;
    }

    /**
     * 他の訪問エンティティと同じかどうかを判定する
     *
     * @param Visit $other
     * @return boolean
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function equals(Visit $other): bool
    {
        if (!$this->identifier->equals($other->identifier)) {
            return false;
        }

        if (!$this->user->equals($other->user)) {
            return false;
        }

        if ($this->visitedAt != $other->visitedAt) {
            return false;
        }

        if (!$this->address->equals($other->address)) {
            return false;
        }

        if ($this->phone != $other->phone) {
            return false;
        }

        if ($this->hasGraveyard != $other->hasGraveyard) {
            return false;
        }

        if ($this->note != $other->note) {
            return false;
        }

        if ($this->result != $other->result) {
            return false;
        }

        return true;
    }
}
