<?php

namespace App\Http\Requests\API\Visit;

use App\Http\Requests\API\AbstractGetRequest;
use App\Validation\Rules;

/**
 * 訪問一覧取得リクエスト.
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
            'sort' => ['nullable', new Rules\Visit\Sort()],
        ];
    }
}
