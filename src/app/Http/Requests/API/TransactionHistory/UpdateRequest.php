<?php

namespace App\Http\Requests\API\TransactionHistory;

use App\Http\Requests\API\AbstractRequest;
use App\Validation\Rules;

/**
 * 取引履歴更新リクエスト
 */
class UpdateRequest extends AbstractRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
          'identifier' => ['required', 'string', 'uuid'],
          'user' => ['required', 'string', 'uuid'],
          'customer' => ['required', 'string', 'uuid'],
          'type' => ['required', new Rules\TransactionHistory\TransactionType()],
          'description' => ['nullable', 'string', 'min:1', 'max:1000'],
          'date' => ['required', 'date'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function routeParameterNames(): array
    {
        return ['identifier'];
    }
}
