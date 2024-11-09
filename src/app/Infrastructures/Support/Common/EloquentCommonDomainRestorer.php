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
     *
     * @param Record $record
     * @return Address
     */
    protected function restoreAddress(Record $record): Address
    {
        return new Address(
            postalCode: new PostalCode(
                first: $record->postal_code_first,
                second: $record->postal_code_second
            ),
            prefecture: Prefecture::from($record->prefecture),
            city: $record->city,
            street: $record->street,
            building: $record->building
        );
    }

    /**
     * レコードから電話番号を復元する.
     *
     * @param Record $record
     * @return PhoneNumber
     */
    protected function restorePhone(Record $record): PhoneNumber
    {
        return new PhoneNumber(
            areaCode: $record->phone_area_code,
            localCode: $record->phone_local_code,
            subscriberNumber: $record->phone_subscriber_number
        );
    }

    /**
     * レコードから日時範囲を復元する.
     *
     * @param Record $record
     * @return DateTimeRange
     */
    protected function restoreDateTimeRange(Record $record): DateTimeRange
    {
        return new DateTimeRange(
            start: CarbonImmutable::parse($record->start),
            end: CarbonImmutable::parse($record->end)
        );
    }
}
