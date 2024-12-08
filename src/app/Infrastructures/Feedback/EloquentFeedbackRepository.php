<?php

namespace App\Infrastructures\Feedback;

use App\Domains\Feedback\Entities\Feedback as Entity;
use App\Domains\Feedback\FeedbackRepository;
use App\Domains\Feedback\ValueObjects\Criteria;
use App\Domains\Feedback\ValueObjects\FeedbackIdentifier;
use App\Domains\Feedback\ValueObjects\FeedbackStatus;
use App\Domains\Feedback\ValueObjects\FeedbackType;
use App\Infrastructures\Common\AbstractEloquentRepository;
use App\Infrastructures\Feedback\Models\Feedback as Record;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Enumerable;

/**
 * フィードバックリポジトリ.
 */
class EloquentFeedbackRepository extends AbstractEloquentRepository implements FeedbackRepository
{
    /**
     * コンストラクタ.
     */
    public function __construct(private readonly Record $builder) {}

    /**
     * {@inheritDoc}
     */
    public function add(Entity $feedback): void
    {
        try {
            $this->createQuery()
                ->create(
                    [
                        'identifier' => $feedback->identifier()->value(),
                        'type' => $feedback->type()->name,
                        'status' => $feedback->status()->name,
                        'content' => $feedback->content(),
                        'created_at' => $feedback->createdAt()->toAtomString(),
                        'updated_at' => $feedback->updatedAt()->toAtomString(),
                    ]
                )
            ;
        } catch (\PDOException $exception) {
            $this->handlePDOException($exception);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function update(Entity $feedback): void
    {
        $target = $this->createQuery()
            ->ofIdentifier($feedback->identifier())
            ->first()
        ;

        if (\is_null($target)) {
            throw new \OutOfBoundsException(\sprintf('Feedback not found: %s', $feedback->identifier()->value()));
        }

        try {
            $target->type = $feedback->type()->name;
            $target->status = $feedback->status()->name;
            $target->content = $feedback->content();
            $target->updated_at = $feedback->updatedAt()->toAtomString();

            $target->save();
        } catch (\PDOException $exception) {
            $this->handlePDOException($exception);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function find(FeedbackIdentifier $identifier): Entity
    {
        $record = $this->createQuery()
            ->ofIdentifier($identifier)
            ->first()
        ;

        if (\is_null($record)) {
            throw new \OutOfBoundsException(\sprintf('Feedback not found: %s', $identifier->value()));
        }

        return $this->restoreEntity($record);
    }

    /**
     * {@inheritDoc}
     */
    public function list(Criteria $criteria): Enumerable
    {
        $records = $this->createQuery()
            ->ofCriteria($criteria)
            ->get()
        ;

        return $records->map(fn (Record $record): Entity => $this->restoreEntity($record));
    }

    /**
     * クエリビルダーを生成する.
     */
    private function createQuery(): Builder
    {
        return $this->builder->newQuery();
    }

    /**
     * レコードからエンティティを復元する.
     */
    private function restoreEntity(Record $record): Entity
    {
        return new Entity(
            identifier: new FeedbackIdentifier($record->identifier),
            type: $this->restoreType($record->type),
            status: $this->restoreStatus($record->status),
            content: $record->content,
            createdAt: CarbonImmutable::parse($record->created_at),
            updatedAt: CarbonImmutable::parse($record->updated_at),
        );
    }

    /**
     * フィードバック種別を復元する.
     */
    private function restoreType(string $type): FeedbackType
    {
        return match ($type) {
            FeedbackType::IMPROVEMENT->name => FeedbackType::IMPROVEMENT,
            FeedbackType::PROBLEM->name => FeedbackType::PROBLEM,
            FeedbackType::QUESTION->name => FeedbackType::QUESTION,
            FeedbackType::OTHER->name => FeedbackType::OTHER,
        };
    }

    /**
     * フィードバックステータスを復元する.
     */
    private function restoreStatus(string $status): FeedbackStatus
    {
        return match ($status) {
            FeedbackStatus::WAITING->name => FeedbackStatus::WAITING,
            FeedbackStatus::IN_PROGRESS->name => FeedbackStatus::IN_PROGRESS,
            FeedbackStatus::COMPLETED->name => FeedbackStatus::COMPLETED,
            FeedbackStatus::NOT_NECESSARY->name => FeedbackStatus::NOT_NECESSARY,
        };
    }
}
