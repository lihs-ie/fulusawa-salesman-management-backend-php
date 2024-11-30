<?php

namespace App\Http\Requests\API\Cemetery;

use App\Http\Requests\API\AbstractRequest;

/**
 * 墓地情報削除リクエスト.
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
    public function routeParameterNames(): array
    {
        return ['identifier'];
    }
}
