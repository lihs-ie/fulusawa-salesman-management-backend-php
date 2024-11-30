<?php

namespace App\Http\Requests\API\Authentication;

use App\Domains\Authentication\ValueObjects\TokenType;
use App\Http\Controllers\API\LazyThrowable;
use App\Http\Requests\API\AbstractRequest;

/**
 * アクセストークン、リフレッシュトークン問わずトークンを送信するリクエスト
 */
class TokenRequest extends AbstractRequest
{
    use LazyThrowable;

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
          'token.type' => ['required', 'string', \sprintf('in:%s,%s', TokenType::ACCESS->name, TokenType::REFRESH->name)],
          'token.value' => ['required', 'string', 'min:1'],
          'token.expiresAt' => ['required', 'date'],
        ];
    }
}
