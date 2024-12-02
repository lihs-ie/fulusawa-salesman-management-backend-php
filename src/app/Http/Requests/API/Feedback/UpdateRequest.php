<?php

namespace App\Http\Requests\API\Feedback;

use App\Http\Requests\API\AbstractRequest;
use App\Validation\Rules;

/**
 * フィードバック更新リクエスト.
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
          'type' => ['required',  new Rules\Feedback\FeedbackType()],
          'status' => ['required', new Rules\Feedback\FeedbackStatus()],
          'content' => ['required', 'string', 'min:1', 'max:1000'],
          'createdAt' => ['required', 'date'],
          'updatedAt' => ['required', 'date', 'after_or_equal:createdAt'],
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
