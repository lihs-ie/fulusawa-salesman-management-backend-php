<?php

namespace App\Http\Encoders\User;

use App\Domains\User\Entities\User;
use App\Http\Encoders\Common\AddressEncoder;
use App\Http\Encoders\Common\PhoneNumberEncoder;

/**
 * ユーザーエンコーダ.
 */
class UserEncoder
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
     * ユーザーをJSONエンコード可能な形式に変換する.
     */
    public function encode(User $user): array
    {
        return [
          'identifier' => $user->identifier()->value(),
          'name' => [
            'first' => $user->firstName(),
            'last' => $user->lastName(),
          ],
          'address' => $this->addressEncoder->encode($user->address()),
          'phone' => $this->phoneEncoder->encode($user->phone()),
          'email' => $user->email()->value(),
          'role' => $user->role()->name,
        ];
    }
}
