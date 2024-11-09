<?php

namespace App\Http\Requests\API;

/**
 * リクエストメソッドがGETのリクエストの基底クラス.
 */
abstract class AbstractGetRequest extends AbstractRequest
{
    /**
     * {@inheritdoc}
     *
     * GETリクエストはリクエストボディが存在しないため、常に（リクエストボディを参照せずに）falseを返す.
     */
    public function isJson(): bool
    {
        return false;
    }
}
