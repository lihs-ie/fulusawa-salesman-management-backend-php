<?php

namespace App\Http\Requests\API\Authentication;

use App\Http\Controllers\API\LazyThrowable;
use App\Http\Requests\API\AbstractRequest;

/**
 * ログアウトリクエスト
 */
class LogoutRequest extends AbstractRequest
{
    use LazyThrowable;

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
          'identifier' => ['required', 'uuid'],
        ];
    }
}
