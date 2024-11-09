<?php

namespace App\Http\Encoders\Authentication;

use App\Domains\Authentication\Entities\Authentication;

/**
 * 認証エンコーダ
 */
class AuthenticationEncoder
{
    /**
     * コンストラクタ.
     */
    public function __construct(private readonly TokenEncoder $tokenEncoder)
    {
    }

    /**
     * 認証をJSONエンコード可能な形式に変換する
     */
    public function encode(Authentication $authentication): array
    {
        return [
          'identifier' => $authentication->identifier()->value(),
          'accessToken' => \is_null($authentication->accessToken()) ?
            null : $this->tokenEncoder->encode($authentication->accessToken()),
          'refreshToken' => \is_null($authentication->refreshToken()) ?
            null : $this->tokenEncoder->encode($authentication->refreshToken())
        ];
    }
}
