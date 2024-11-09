<?php

namespace App\Http\Requests\API\Cemetery;

use App\Http\Controllers\API\LazyThrowable;
use App\Http\Requests\API\AbstractGetRequest;

/**
 * 墓地情報取得リクエスト.
 */
class FindRequest extends AbstractGetRequest
{
    use LazyThrowable;

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
