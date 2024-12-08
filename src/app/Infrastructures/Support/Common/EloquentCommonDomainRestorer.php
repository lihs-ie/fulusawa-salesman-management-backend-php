<?php

namespace App\Infrastructures\Support\Common;

use App\Domains\Common\ValueObjects\Address;
use App\Domains\Common\ValueObjects\DateTimeRange;
use App\Domains\Common\ValueObjects\PhoneNumber;
use App\Domains\Common\ValueObjects\PostalCode;
use App\Domains\Common\ValueObjects\Prefecture;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model as Record;

/**
 * レコードから共通ドメインを復元するへルパ.
 */
trait EloquentCommonDomainRestorer
{
    /**
     * レコードから住所を復元する.
     */
    protected function restoreAddress(Record $record): Address
    {
        $address = \json_decode($record->address, true);

        return new Address(
            postalCode: new PostalCode(
                first: $address['postalCode']['first'],
                second: $address['postalCode']['second']
            ),
            prefecture: Prefecture::from($address['prefecture']),
            city: $address['city'],
            street: $address['street'],
            building: $address['building']
        );
    }

    /**
     * レコードから電話番号を復元する.
     */
    protected function restorePhone(Record $record): PhoneNumber
    {
        $phone = \json_decode($record->phone_number, true);

        return new PhoneNumber(
            areaCode: $phone['areaCode'],
            localCode: $phone['localCode'],
            subscriberNumber: $phone['subscriberNumber']
        );
    }

    /**
     * レコードから日時範囲を復元する.
     */
    protected function restoreDateTimeRange(Record $record): DateTimeRange
    {
        return new DateTimeRange(
            start: CarbonImmutable::parse($record->start),
            end: CarbonImmutable::parse($record->end)
        );
    }
}
