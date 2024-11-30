<?php

namespace App\Http\Requests\API\Cemetery;

use App\Http\Controllers\API\LazyThrowable;
use App\Http\Requests\API\AbstractRequest;
use App\Validation\Rules;

/**
 * 墓地情報更新リクエスト.
 */
class UpdateRequest extends AbstractRequest
{
    use LazyThrowable;

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
          'identifier' => ['required', 'string', 'uuid'],
          'customer' => ['required', 'string', 'uuid'],
          'name' => ['required', 'string', 'max:255'],
          'type' => ['required', 'string', new Rules\Cemetery\CemeteryType()],
          'construction' => ['required', 'string', 'date'],
          'inHouse' => ['required', 'boolean'],
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
