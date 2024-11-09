<?php

namespace App\Http\Requests\API\TransactionHistory;

use App\Http\Requests\API\AbstractGetRequest;

/**
 * 取引履歴取得リクエスト.
 */
class FindRequest extends AbstractGetRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
          'identifier' => ['required', 'string', 'uuid'],
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
