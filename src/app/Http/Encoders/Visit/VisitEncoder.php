<?php

namespace App\Http\Encoders\Visit;

use App\Domains\Visit\Entities\Visit;
use App\Http\Encoders\Common\AddressEncoder;
use App\Http\Encoders\Common\PhoneNumberEncoder;

/**
 * 訪問エンコーダ.
 */
class VisitEncoder
{
    /**
     * コンストラクタ.
     */
    public function __construct(
        private readonly AddressEncoder $addressEncoder,
        private readonly PhoneNumberEncoder $phoneEncoder,
    ) {
    }

    /**
     * 訪問をJSONエンコード可能な形式に変換する.
     */
    public function encode(Visit $visit): array
    {
        return [
          'identifier' => $visit->identifier()->value(),
          'user' => $visit->user()->value(),
          'visitedAt' => $visit->visitedAt()->toAtomString(),
          'address' => $this->addressEncoder->encode($visit->address()),
          'phone' => \is_null($visit->phone()) ? null : $this->phoneEncoder->encode($visit->phone()),
          'hasGraveyard' => $visit->hasGraveyard(),
          'note' => $visit->note(),
          'result' => $visit->result()->name,
        ];
    }
}
