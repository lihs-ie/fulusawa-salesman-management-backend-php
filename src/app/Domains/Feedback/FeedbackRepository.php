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
    /**
     * フィードバックを追加する
     *
     * @param Feedback $feedback
     * @return void
     *
     * @throws ConflictException フィードバックが既に存在する場合
     */
    public function add(Feedback $feedback): void;

    /**
     * フィードバックを更新する
     *
     * @param Feedback $feedback
     * @return void
     *
     * @throws \OutOfBoundsException フィードバックが存在しない場合
     */
    public function update(Feedback $feedback): void;

    /**
     * フィードバックを取得する
     *
     * @param FeedbackIdentifier $identifier
     * @return Feedback
     *
     * @throws \OutOfBoundsException フィードバックが存在しない場合
     */
    public function find(FeedbackIdentifier $identifier): Feedback;

    /**
     * フィードバック一覧を取得する
     *
     * @param Criteria $criteria
     * @return Enumerable
     */
    public function list(Criteria $criteria): Enumerable;
}
