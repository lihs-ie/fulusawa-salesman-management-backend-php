<?php

namespace App\Http\Requests\API\DailyReport;

use App\Http\Requests\API\AbstractRequest;

/**
 * 日報削除リクエスト.
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
    protected function routeParameterNames(): array
    {
        return ['identifier'];
    }
}
