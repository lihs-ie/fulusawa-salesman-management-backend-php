<?php

namespace App\Http\Requests\API\Customer;

use App\Http\Requests\API\AbstractRequest;

/**
 * 顧客取得リクエスト.
 */
class FindRequest extends AbstractRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
          'identifier' => ['required', 'uuid'],
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
