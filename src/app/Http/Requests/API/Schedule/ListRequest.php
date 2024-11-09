<?php

namespace App\Http\Requests\API\Schedule;

use App\Http\Requests\API\AbstractGetRequest;
use App\Validation\Rules;

/**
 * スケジュール一覧取得リクエスト.
 */
class ListRequest extends AbstractGetRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
          'status' => ['nullable', new Rules\Schedule\ScheduleStatus()],
          'date' => ['nullable', new Rules\Common\DateTimeRange()],
        ];
    }
}
