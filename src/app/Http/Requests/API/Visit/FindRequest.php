<?php

namespace App\Http\Requests\API\Visit;

use App\Http\Requests\API\AbstractGetRequest;

/**
 * 訪問取得リクエスト.
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
