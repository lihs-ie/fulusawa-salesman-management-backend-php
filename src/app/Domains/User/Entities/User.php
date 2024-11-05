<?php

namespace App\Domains\User\Entities;

use App\Domains\Common\ValueObjects\Address;
use App\Domains\Common\ValueObjects\MailAddress;
use App\Domains\Common\ValueObjects\PhoneNumber;
use App\Domains\User\ValueObjects\Role;
use App\Domains\User\ValueObjects\UserIdentifier;

/**
 * ユーザーを表すエンティティ
 */
class User
{
    public function __construct(
        public readonly UserIdentifier $identifier,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly Address $address,
        public readonly PhoneNumber $phone,
        public readonly MailAddress $email,
        public readonly string $password,
        public readonly Role $role,
    ) {
        if ($firstName === '') {
            throw new \InvalidArgumentException('First name must not be empty');
        }

        if ($lastName === '') {
            throw new \InvalidArgumentException('Last name must not be empty');
        }
    }

    public function identifier(): UserIdentifier
    {
        return $this->identifier;
    }

    public function firstName(): string
    {
        return $this->firstName;
    }

    public function lastName(): string
    {
        return $this->lastName;
    }

    public function address(): Address
    {
        return $this->address;
    }

    public function phone(): PhoneNumber
    {
        return $this->phone;
    }

    public function email(): MailAddress
    {
        return $this->email;
    }

    public function password(): string
    {
        return $this->password;
    }

    public function role(): Role
    {
        return $this->role;
    }

    /**
     * 与えられた値が自信と同一か判定する
     *
     * @param self|null $other
     * @return boolean
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function equals(?self $other): bool
    {
        if (\is_null($other)) {
            return false;
        }

        if (!$this->identifier->equals($other->identifier)) {
            return false;
        }

        if ($this->firstName !== $other->firstName) {
            return false;
        }

        if ($this->lastName !== $other->lastName) {
            return false;
        }

        if (!$this->address->equals($other->address)) {
            return false;
        }

        if (!$this->phone->equals($other->phone)) {
            return false;
        }

        if (!$this->email->equals($other->email)) {
            return false;
        }

        if ($this->password !== $other->password) {
            return false;
        }

        if ($this->role !== $other->role) {
            return false;
        }

        return true;
    }
}
