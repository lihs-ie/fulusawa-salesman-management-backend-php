<?php

namespace App\Http\Requests\API\DailyReport;

use App\Http\Requests\API\AbstractGetRequest;

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
          'date' => ['nullable', 'array'],
          'date.start' => ['nullable', 'date'],
          'date.end' => ['nullable', 'date', 'after_or_equal:date.start'],
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
