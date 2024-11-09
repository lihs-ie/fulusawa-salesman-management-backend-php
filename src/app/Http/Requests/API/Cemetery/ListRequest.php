<?php

namespace App\Http\Requests\API\Cemetery;

use App\Http\Controllers\API\LazyThrowable;
use App\Http\Requests\API\AbstractGetRequest;

/**
 * 墓地情報一覧取得リクエスト.
 */
class ListRequest extends AbstractGetRequest
{
    use LazyThrowable;

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
          'customer' => ['nullable', 'string', 'uuid']
        ];
    }
}
