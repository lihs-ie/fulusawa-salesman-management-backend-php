<?php

namespace App\Infrastructures\Support\Common;

use App\Domains\Common\ValueObjects\Address;
use App\Domains\Common\ValueObjects\PhoneNumber;

/**
 * Eloquentの共通ドメインデフレータ.
 */
trait EloquentCommonDomainDeflator
{
    /**
     * 住所をJSON文字列に変換する.
     */
    protected function deflateAddress(Address $address): string
    {
        return \json_encode([
            'postalCode' => [
                'first' => $address->postalCode()->first(),
                'second' => $address->postalCode()->second(),
            ],
            'prefecture' => $address->prefecture()->value,
            'city' => $address->city(),
            'street' => $address->street(),
            'building' => $address->building(),
        ]);
    }

    /**
     * 電話番号をJSON文字列に変換する.
     */
    protected function deflatePhoneNumber(PhoneNumber $phone): string
    {
        return \json_encode([
            'areaCode' => $phone->areaCode(),
            'localCode' => $phone->localCode(),
            'subscriberNumber' => $phone->subscriberNumber(),
        ]);
    }
}
