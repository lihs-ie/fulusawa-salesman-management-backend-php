<?php

namespace App\Http\Requests\API\DailyReport;

use App\Http\Requests\API\AbstractGetRequest;
use App\Validation\Rules;

/**
 * 日報一覧取得リクエスト.
 */
class ListRequest extends AbstractGetRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            'user' => ['nullable', 'string', 'uuid'],
            'date' => ['nullable', new Rules\Common\DateTimeRange()],
            'isSubmitted' => ['nullable', 'boolean'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function routeParameterNames(): array
    {
        return [];
    }
}
