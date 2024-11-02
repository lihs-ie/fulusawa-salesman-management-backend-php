<?php

namespace App\UseCases\Factories;

use App\Domains\Common\ValueObjects\Address;
use App\Domains\Common\ValueObjects\DateTimeRange;
use App\Domains\Common\ValueObjects\PhoneNumber;
use App\Domains\Common\ValueObjects\PostalCode;
use App\Domains\Common\ValueObjects\Prefecture;
use Carbon\CarbonImmutable;
use Ramsey\Uuid\Uuid;

/**
 * 共通ドメインの値を抽出する
 */
class CommonDomainFactory
{
    /**
     * 配列から住所を取り出す
     *
     * @param array $address
     * @return Address
     */
    public function extractAddress(array $address): Address
    {
        $postalCode = $this->extractPostalCode($this->extractArray($address, 'postalCode'));
        $prefecture = Prefecture::from($this->extractInteger($address, 'prefecture'));

        return new Address(
            postalCode: $postalCode,
            prefecture: $prefecture,
            city: $this->extractString($address, 'city'),
            street: $this->extractString($address, 'street'),
            building: $this->extractString($address, 'building')
        );
    }

    /**
     * 配列から郵便番号を取り出す
     *
     * @param array $postalCode
     * @return PostalCode
     */
    public function extractPostalCode(array $postalCode): PostalCode
    {
        return new PostalCode(
            first: $this->extractString($postalCode, 'first'),
            second: $this->extractString($postalCode, 'second')
        );
    }

    /**
     * 配列から電話番号を取り出す
     *
     * @param array $phone
     * @return PhoneNumber
     */
    public function extractPhone(array $phone): PhoneNumber
    {
        return new PhoneNumber(
            areaCode: $this->extractString($phone, 'areaCode'),
            localCode: $this->extractString($phone, 'localCode'),
            subscriberNumber: $this->extractString($phone, 'subscriberNumber')
        );
    }

    /**
     * 配列から日時範囲を取り出す
     *
     * @param array $range
     * @return DateTimeRange
     */
    public function extractDateTimeRange(array $range): DateTimeRange|null
    {
        $start = $this->extractString($range, 'start');
        $end = $this->extractString($range, 'end');

        if (\is_null($start) && \is_null($end)) {
            return null;
        }

        return new DateTimeRange(
            start: \is_null($start) ? null : CarbonImmutable::parse($start),
            end: \is_null($end) ? null : CarbonImmutable::parse($end)
        );
    }

    /**
     * 配列からkeyを指定して値を取り出す
     *
     * @param array $conditions
     * @param string $key
     * @return mixed
     */
    public function extractString(array $conditions, string $key): mixed
    {
        if (!isset($conditions[$key])) {
            return null;
        }

        return $conditions[$key];
    }

    /**
     * 配列から数値を取り出す
     *
     * @param array $conditions
     * @param string $key
     * @return int|null
     */
    public function extractInteger(array $conditions, string $key): int|null
    {
        $target = isset($conditions[$key]) ? $conditions[$key] : null;

        if (\is_null($target)) {
            return null;
        }

        if (!\is_numeric($target)) {
            throw new \InvalidArgumentException(\sprintf('Key %s is not numeric', $key));
        }

        return (int) $target;
    }

    /**
     * 配列から配列を取り出す
     *
     * @param array $conditions
     * @param string $key
     * @return array|null
     */
    public function extractArray(array $conditions, string $key): array|null
    {
        $target = isset($conditions[$key]) ? $conditions[$key] : null;

        if (\is_null($target)) {
            return null;
        }

        if (!\is_array($target)) {
            throw new \InvalidArgumentException(\sprintf('Key %s is not array', $key));
        }

        return $target;
    }

    /**
     * 配列から真偽値を取り出す
     *
     * @param array $conditions
     * @param string $key
     * @return bool|null
     */
    public function extractBoolean(array $conditions, string $key): bool|null
    {
        $target = $this->extractString($conditions, $key);

        if (\is_null($target)) {
            return null;
        }

        if ($target === 'true') {
            return true;
        }

        if ($target === 'false') {
            return false;
        }

        if (!\is_bool($target)) {
            throw new \InvalidArgumentException(\sprintf('Key %s is not boolean', $key));
        }

        return $target;
    }
}
