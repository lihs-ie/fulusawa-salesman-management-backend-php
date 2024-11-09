<?php

namespace App\Http\Requests\API\Schedule;

use App\Http\Requests\API\AbstractRequest;
use App\Validation\Rules;

/**
 * スケジュール追加リクエスト.
 */
class AddRequest extends AbstractRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
          'identifier' => ['required', 'string', 'uuid'],
          'user' => ['required', 'string', 'uuid'],
          'customer' => ['nullable', 'string', 'uuid'],
          'title' => ['required', 'string', 'max:255'],
          'description' => ['nullable', 'string', 'max:1000'],
          'date' => ['required', new Rules\Common\DateTimeRange()],
          'status' => ['required', new Rules\Schedule\ScheduleStatus()],
          'repeatFrequency' => ['nullable', new Rules\Schedule\RepeatFrequency()],
        ];
    }
}
