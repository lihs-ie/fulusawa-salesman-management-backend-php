<?php

namespace App\Http\Encoders\Cemetery;

use App\Domains\Cemetery\Entities\Cemetery;

/**
 * 墓地情報エンコーダ
 */
class CemeteryEncoder
{
    /**
     * 墓地情報をエンコードする
     *
     * @param Cemetery $cemetery
     * @return array
     */
    public function encode(Cemetery $cemetery): array
    {
        return [
          'identifier' => $cemetery->identifier()->value(),
          'customer' => $cemetery->customer()->value(),
          'name' => $cemetery->name(),
          'type' => $cemetery->type()->name,
          'construction' => $cemetery->construction()->toAtomString(),
          'inHouse' => $cemetery->inHouse(),
        ];
    }
}
