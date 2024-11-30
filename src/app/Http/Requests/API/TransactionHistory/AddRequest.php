<?php

namespace App\Http\Requests\API\TransactionHistory;

use App\Http\Requests\API\AbstractRequest;
use App\Validation\Rules;

/**
 * 取引履歴追加リクエスト
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
          'customer' => ['required', 'uuid'],
          'type' => ['required', new Rules\TransactionHistory\TransactionType()],
          'description' => ['nullable', 'string', 'min:1', 'max:1000'],
          'date' => ['required', 'date'],
        ];
    }
}
