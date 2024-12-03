<?php

namespace App\Http\Encoders\Feedback;

use App\Domains\Feedback\Entities\Feedback;

/**
 * フィードバックエンコーダ.
 */
class FeedbackEncoder
{
    /**
     * フィードバックをJSONエンコード可能な形式に変換する.
     */
    public function encode(Feedback $feedback): array
    {
        return [
          'identifier' => $feedback->identifier()->value(),
          'type' => $feedback->type()->name,
          'status' => $feedback->status()->name,
          'content' => $feedback->content(),
          'createdAt' => $feedback->createdAt()->toAtomString(),
          'updatedAt' => $feedback->updatedAt()->toAtomString(),
        ];
    }
}
