<?php

namespace App\Http\Encoders\Authentication;

use App\Domains\Authentication\ValueObjects\Token;

/**
 * トークンエンコーダ
 */
class TokenEncoder
{
    /**
     * トークンをJSONエンコード可能な形式に変換する
     */
    public function encode(Token $token): array
    {
        return [
          'type' => $token->type()->name,
          'value' => $token->value(),
          'expiresAt' => $token->expiresAt()->toAtomString()
        ];
    }
}
