<?php

namespace App\Http\Requests\API\User;

use App\Http\Requests\API\AbstractGetRequest;

/**
 * ユーザー一覧取得リクエスト
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
