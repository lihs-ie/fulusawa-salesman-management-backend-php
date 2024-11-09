<?php

namespace App\Http\Requests\API\Authentication;

use App\Http\Controllers\API\LazyThrowable;
use App\Http\Requests\API\AbstractRequest;

/**
 * ログインリクエスト
 */
class LoginRequest extends AbstractRequest
{
    use LazyThrowable;

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
          'identifier' => ['required', 'uuid'],
          'email' => ['required', 'string', 'email'],
          'password' => ['required', 'string', 'min:8', 'max:255'],
        ];
    }
}
