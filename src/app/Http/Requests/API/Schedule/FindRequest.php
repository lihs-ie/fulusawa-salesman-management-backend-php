<?php

namespace App\Http\Requests\API\Schedule;

use App\Http\Requests\API\AbstractGetRequest;

/**
 * スケジュール取得リクエスト.
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
