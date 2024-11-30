<?php

namespace App\Infrastructures\Feedback;

use App\Domains\Feedback\Entities\Feedback as Entity;
use App\Domains\Feedback\FeedbackRepository;
use App\Domains\Feedback\ValueObjects\Criteria;
use App\Domains\Feedback\ValueObjects\Criteria\Sort;
use App\Domains\Feedback\ValueObjects\FeedbackIdentifier;
use App\Domains\Feedback\ValueObjects\FeedbackStatus;
use App\Domains\Feedback\ValueObjects\FeedbackType;
use App\Infrastructures\Feedback\Models\Feedback as Record;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Enumerable;

/**
 * フィードバックリポジトリ
 */
class EloquentFeedbackRepository implements FeedbackRepository
{
    /**
     * コンストラクタ.
     *
     * @param Record $builder
     */
    public function __construct(private readonly Record $builder)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function persist(Entity $feedback): void
    {
        $this->createQuery()
            ->updateOrCreate(
                ['identifier' => $feedback->identifier()->value()],
                [
                    'identifier' => $feedback->identifier()->value(),
                    'type' => $feedback->type()->name,
                    'status' => $feedback->status()->name,
                    'content' => $feedback->content(),
                    'created_at' => $feedback->createdAt()->toAtomString(),
                    'updated_at' => $feedback->updatedAt()->toAtomString(),
                ]
            );
    }

    /**
     * {@inheritDoc}
     */
    public function find(FeedbackIdentifier $identifier): Entity
    {
        $record = $this->createQuery()
            ->where('identifier', $identifier->value())
            ->first();

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
            ->when(
                !\is_null($criteria->type()),
                fn (Builder $query): Builder => $query->where('type', $criteria->type()->name)
            )
            ->when(
                !\is_null($criteria->status()),
                fn (Builder $query): Builder => $query->where('status', $criteria->status()->name)
            )
            ->when(
                !\is_null($criteria->sort()),
                function (Builder $query) use ($criteria): Builder {
                    return match ($criteria->sort()) {
                        Sort::CREATED_AT_ASC => $query->orderBy('created_at', 'asc'),
                        Sort::CREATED_AT_DESC => $query->orderBy('created_at', 'desc'),
                        Sort::UPDATED_AT_ASC => $query->orderBy('updated_at', 'asc'),
                        Sort::UPDATED_AT_DESC => $query->orderBy('updated_at', 'desc'),
                    };
                }
            )
            ->get();

        return $records->map(fn (Record $record): Entity => $this->restoreEntity($record));
    }

    /**
     * クエリビルダーを生成する.
     *
     * @return Builder
     */
    private function createQuery(): Builder
    {
        return $this->builder->newQuery();
    }

    /**
     * レコードからエンティティを復元する.
     *
     * @param Record $record
     * @return Entity
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
     *
     * @param string $type
     * @return FeedbackType
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
     *
     * @param string $status
     * @return FeedbackStatus
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
