<?php

namespace App\Http\Requests\API\DailyReport;

use App\Http\Requests\API\AbstractRequest;

/**
 * 日報更新リクエスト.
 */
class UpdateRequest extends AbstractRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
          'identifier' => ['required', 'string', 'uuid'],
          'user' => ['required', 'string', 'uuid'],
          'date' => ['required', 'date'],
          'schedules' => ['array'],
          'schedules.*' => ['string', 'uuid'],
          'visits' => ['array'],
          'visits.*' => ['string', 'uuid'],
          'isSubmitted' => ['required', 'boolean'],
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
