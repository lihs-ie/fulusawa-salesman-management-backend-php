<?php

namespace App\Http\Requests\API\User;

use App\Http\Requests\API\AbstractRequest;

/**
 * ユーザー削除リクエスト.
 */
class DeleteRequest extends AbstractRequest
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
