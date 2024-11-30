<?php

namespace App\Http\Requests\API\Customer;

use App\Http\Requests\API\AbstractRequest;

/**
 * 顧客削除リクエスト.
 */
class DeleteRequest extends AbstractRequest
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
