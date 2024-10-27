<?php

namespace App\Domains\Common\ValueObjects;

/**
 * 住所を表す値オブジェクト
 */
class Address
{
    public function __construct(
        public readonly PostalCode $postalCode,
        public readonly Prefecture $prefecture,
        public readonly string $city,
        public readonly string $street,
        public readonly string|null $building
    ) {
        if ($city === '') {
            throw new \InvalidArgumentException('City must not be empty');
        }

        if ($street === '') {
            throw new \InvalidArgumentException('Street must not be empty');
        }

        if ($building !== null && $building === '') {
            throw new \InvalidArgumentException('Building must not be empty');
        }
    }

    public function equals(Address $other): bool
    {
        if (!$this->postalCode->equals($other->postalCode)) {
            return false;
        }

        if ($this->prefecture !== $other->prefecture) {
            return false;
        }

        if ($this->city !== $other->city) {
            return false;
        }

        if ($this->street !== $other->street) {
            return false;
        }

        if ($this->building !== $other->building) {
            return false;
        }

        return true;
    }


    public function postalCode(): PostalCode
    {
        return $this->postalCode;
    }

    public function prefecture(): Prefecture
    {
        return $this->prefecture;
    }

    public function city(): string
    {
        return $this->city;
    }

    public function street(): string
    {
        return $this->street;
    }

    public function building(): string|null
    {
        return $this->building;
    }


    public function __toString(): string
    {
        return \sprintf(
            '%s %s %s%s',
            $this->postalCode,
            $this->prefecture,
            $this->city,
            $this->building !== null ? ' ' . $this->building : ''
        );
    }
}
