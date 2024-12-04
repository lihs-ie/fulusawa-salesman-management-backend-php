<?php

namespace App\Http\Requests\API\Schedule;

use App\Http\Requests\API\AbstractRequest;
use App\Validation\Rules;

/**
 * スケジュール更新リクエスト.
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
            'participants' => ['required', 'array', 'between:1,10'],
            'participants.*' => ['required', 'string', 'uuid'],
            'creator' => ['required', 'string', 'uuid'],
            'updater' => ['required', 'string', 'uuid'],
            'customer' => ['nullable', 'string', 'uuid'],
            'content' => ['required', new Rules\Schedule\ScheduleContent()],
            'date' => ['required', new Rules\Common\DateTimeRange()],
            'status' => ['required', new Rules\Schedule\ScheduleStatus()],
            'repeatFrequency' => ['nullable', new Rules\Schedule\RepeatFrequency()],
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
