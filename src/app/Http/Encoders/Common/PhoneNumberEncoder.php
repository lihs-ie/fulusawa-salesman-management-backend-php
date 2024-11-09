<?php

namespace App\Http\Encoders\Common;

use App\Domains\Common\ValueObjects\PhoneNumber;

/**
 * 電話番号エンコーダ.
 */
class PhoneNumberEncoder
{
    /**
     * 電話番号をJSONエンコード可能な形式に変換する.
     */
    public function encode(PhoneNumber $phone): array
    {
        return [
          'areaCode' => $phone->areaCode(),
          'localCode' => $phone->localCode(),
          'subscriberNumber' => $phone->subscriberNumber(),
        ];
    }
}
