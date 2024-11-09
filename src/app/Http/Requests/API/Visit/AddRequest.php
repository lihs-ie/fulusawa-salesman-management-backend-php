<?php

namespace App\Http\Requests\API\Visit;

use App\Http\Requests\API\AbstractRequest;
use App\Validation\Rules;

/**
 * 訪問追加リクエスト
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
          'user' => ['required', 'uuid'],
          'visitedAt' => ['required', 'date'],
          'address' => ['required', new Rules\Common\Address()],
          'phone' => ['nullable', new Rules\Common\PhoneNumber()],
          'hasGraveyard' => ['required', 'boolean'],
          'note' => ['nullable', 'string', 'min:1', 'max:1000'],
          'result' => ['required', new Rules\Visit\VisitResult()],
        ];
    }
}
