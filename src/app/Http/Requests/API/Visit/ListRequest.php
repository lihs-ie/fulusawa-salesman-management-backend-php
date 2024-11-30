<?php

namespace App\Http\Requests\API\Visit;

use App\Http\Requests\API\AbstractGetRequest;

/**
 * 訪問一覧取得リクエスト
 */
class ListRequest extends AbstractGetRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [];
    }
}
