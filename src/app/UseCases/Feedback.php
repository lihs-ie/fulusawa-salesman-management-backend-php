<?php

namespace App\UseCases;

use App\Domains\Feedback\Entities\Feedback as Entity;
use App\Domains\Feedback\FeedbackRepository;
use App\Domains\Feedback\ValueObjects\Criteria;
use App\Domains\Feedback\ValueObjects\Criteria\Sort;
use App\Domains\Feedback\ValueObjects\FeedbackIdentifier;
use App\Domains\Feedback\ValueObjects\FeedbackStatus;
use App\Domains\Feedback\ValueObjects\FeedbackType;
use App\UseCases\Factories\CommonDomainFactory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Enumerable;

/**
 * フィードバックユースケース
 */
class Feedback
{
    use CommonDomainFactory;

    public function __construct(
        private readonly FeedbackRepository $repository,
    ) {
    }

    /**
     * フィードバックを永続化する
     *
     * @param string $identifier
     * @param int $type
     * @param int $status
     * @param string $content
     * @param string $createdAt
     * @param string $updatedAt
     * @return void
     */
    public function persist(
        string $identifier,
        int $type,
        int $status,
        string $content,
        string $createdAt,
        string $updatedAt
    ): void {
        $entity = new Entity(
            identifier: new FeedbackIdentifier($identifier),
            type: $this->convertType($type),
            status: $this->convertStatus($status),
            content: $content,
            createdAt: CarbonImmutable::parse($createdAt),
            updatedAt: CarbonImmutable::parse($updatedAt),
        );


        $this->repository->persist($entity);
    }

    /**
     * フィードバックを取得する
     *
     * @param string $identifier
     * @return Entity
     */
    public function find(string $identifier): Entity
    {
        return $this->repository->find(new FeedbackIdentifier($identifier));
    }

    /**
     * フィードバック一覧を取得する
     *
     * @param array $conditions
     * @return Enumerable<Entity>
     */
    public function list(array $conditions): Enumerable
    {
        $criteria = $this->createCriteria($conditions);

        return $this->repository->list($criteria);
    }

    /**
     * 文字列からフィードバックステータスに変換する
     *
     * @param integer $status
     * @return FeedbackStatus
     */
    private function convertStatus(string $status): FeedbackStatus
    {
        return match ($status) {
            '1' => FeedbackStatus::WAITING,
            '2' => FeedbackStatus::IN_PROGRESS,
            '3' => FeedbackStatus::COMPLETED,
            '4' => FeedbackStatus::NOT_NECESSARY,
        };
    }

    /**
     * 文字列からフィードバックタイプに変換する
     *
     * @param integer $type
     * @return FeedbackType
     */
    private function convertType(string $type): FeedbackType
    {
        return match ($type) {
            '1' => FeedbackType::IMPROVEMENT,
            '2' => FeedbackType::PROBLEM,
            '3' => FeedbackType::QUESTION,
            '4' => FeedbackType::OTHER,
        };
    }

    /**
     * 検索条件を生成する
     *
     * @param array $conditions
     * @return Criteria
     */
    private function createCriteria(array $conditions): Criteria
    {
        $status = $this->extractString($conditions, 'status');
        $type = $this->extractString($conditions, 'type');
        $sort = $this->extractString($conditions, 'sort');

        return new Criteria(
            status: \is_null($status) ? null : $this->convertStatus($status),
            type: \is_null($type) ? null : $this->convertType($type),
            sort: \is_null($sort) ? null : $this->convertSort($sort),
        );
    }

    /**
     * 文字列からフィードバックソートに変換する
     *
     * @param string $sort
     * @return Sort
     */
    private function convertSort(string $sort): Sort
    {
        return match ($sort) {
            '1' => Sort::CREATED_AT_DESC,
            '2' => Sort::CREATED_AT_ASC,
            '3' => Sort::UPDATED_AT_DESC,
            '4' => Sort::UPDATED_AT_ASC,
        };
    }
}
