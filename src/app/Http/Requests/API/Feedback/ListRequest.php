<?php

namespace App\Http\Requests\API\Feedback;

use App\Http\Requests\API\AbstractGetRequest;
use App\Validation\Rules\Feedback;

/**
 * フィードバック一覧取得リクエスト.
 */
class ListRequest extends AbstractGetRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
          'status' => ['nullable', new Feedback\FeedbackStatus()],
          'type' => ['nullable', new Feedback\FeedbackType()],
          'sort' => ['nullable', new Feedback\Sort()],
        ];
    }
}
