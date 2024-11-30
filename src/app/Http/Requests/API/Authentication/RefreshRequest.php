<?php

namespace App\Http\Requests\API\Authentication;

use App\Domains\Authentication\ValueObjects\TokenType;

/**
 * リフレッシュリクエスト
 */
class RefreshRequest extends TokenRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
          ...parent::rules(),
          'token.type' => ['required', 'string', \sprintf('in:%s', TokenType::REFRESH->name)]
        ];
    }
}
