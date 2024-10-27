<?php

namespace App\Domains\Feedback;

use App\Domains\Feedback\Entities\Feedback;
use App\Domains\Feedback\ValueObjects\FeedbackIdentifier;
use App\Domains\Feedback\ValueObjects\Criteria;
use Illuminate\Support\Enumerable;

/**
 * フィードバックリポジトリ
 */
interface FeedbackRepository
{
    public function persist(Feedback $feedback): void;

    public function find(FeedbackIdentifier $identifier): Feedback;

    public function list(Criteria $criteria): Enumerable;
}
