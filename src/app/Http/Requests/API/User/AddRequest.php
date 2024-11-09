<?php

namespace App\Http\Requests\API\User;

use App\Http\Requests\API\AbstractRequest;
use App\Validation\Rules;

/**
 * ユーザー追加リクエスト
 */
class AddRequest extends AbstractRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
          'identifier' => ['required', 'uuid'],
          'name' => ['required', 'array'],
          'name.first' => ['required', 'string', 'min:1', 'max:255'],
          'name.last' => ['required', 'string', 'min:1', 'max:255'],
          'address' => ['required', new Rules\Common\Address()],
          'phone' => ['required', new Rules\Common\PhoneNumber()],
          'email' => ['required', 'email'],
          'password' => ['required', new Rules\User\Password()],
          'role' => ['required', new Rules\User\Role()],
        ];
    }
}
