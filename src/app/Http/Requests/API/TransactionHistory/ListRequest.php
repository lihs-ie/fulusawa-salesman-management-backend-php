<?php

namespace App\Http\Requests\API\TransactionHistory;

use App\Http\Requests\API\AbstractGetRequest;
use App\Validation\Rules;

/**
 * 取引履歴一覧取得リクエスト.
 */
class ListRequest extends AbstractGetRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            'user' => ['nullable', 'uuid'],
            'customer' => ['nullable', 'uuid'],
            'sort' => ['nullable', new Rules\TransactionHistory\Sort()],
        ];
    }
}
