<?php

namespace App\Http\Encoders\Common;

use App\Domains\Common\ValueObjects\Address;

/**
 * 住所エンコーダ.
 */
class AddressEncoder
{
    /**
     * 住所をJSONエンコード可能な形式に変換する.
     */
    public function encode(Address $address): array
    {
        return [
          'postalCode' => [
            'first' => $address->postalCode()->first(),
            'second' => $address->postalCode()->second(),
          ],
          'prefecture' => $address->prefecture()->value,
          'city' => $address->city(),
          'street' => $address->street(),
          'building' => $address->building(),
        ];
    }
}
