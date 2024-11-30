<?php

namespace App\Http\Requests\API\User;

use App\Http\Requests\API\AbstractGetRequest;

/**
 * ユーザー取得リクエスト.
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
