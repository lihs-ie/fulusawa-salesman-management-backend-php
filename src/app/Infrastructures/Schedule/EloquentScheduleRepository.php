<?php

namespace App\Infrastructures\Schedule;

use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\Domains\Schedule\Entities\Schedule as Entity;
use App\Domains\Schedule\ScheduleRepository;
use App\Domains\Schedule\ValueObjects\Criteria;
use App\Domains\Schedule\ValueObjects\FrequencyType;
use App\Domains\Schedule\ValueObjects\RepeatFrequency;
use App\Domains\Schedule\ValueObjects\ScheduleIdentifier;
use App\Domains\Schedule\ValueObjects\ScheduleStatus;
use App\Domains\User\ValueObjects\UserIdentifier;
use App\Infrastructures\Schedule\Models\Schedule as Record;
use App\Infrastructures\Support\Common\EloquentCommonDomainRestorer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Enumerable;

/**
 * スケジュールリポジトリ
 */
class EloquentScheduleRepository implements ScheduleRepository
{
    use EloquentCommonDomainRestorer;

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
    public function persist(Entity $schedule): void
    {
        $repeat = \is_null($schedule->repeat()) ? null : \json_encode([
            'type' => $schedule->repeat()->type()->name,
            'interval' => $schedule->repeat()->interval(),
        ]);

        $this->createQuery()
            ->updateOrCreate(
                ['identifier' => $schedule->identifier()->value()],
                [
                    'identifier' => $schedule->identifier()->value(),
                    'user' => $schedule->user()->value(),
                    'customer' => $schedule->customer()?->value(),
                    'title' => $schedule->title(),
                    'description' => $schedule->description(),
                    'start' => $schedule->date()->start()->toAtomString(),
                    'end' => $schedule->date()->end()->toAtomString(),
                    'status' => $schedule->status()->name,
                    'repeat' => $repeat,
                ]
            );
    }

    /**
     * {@inheritDoc}
     */
    public function find(ScheduleIdentifier $identifier): Entity
    {
        $record = $this->createQuery()
            ->where('identifier', $identifier->value())
            ->first();

        if (\is_null($record)) {
            throw new \OutOfBoundsException(\sprintf('Schedule not found: %s', $identifier->value()));
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
                !\is_null($criteria->status()),
                fn (Builder $query): Builder => $query->where('status', $criteria->status()->name)
            )
            ->when(
                !\is_null($criteria->date()),
                function (Builder $query) use ($criteria): Builder {
                    $date = $criteria->date();

                    if (!\is_null($date->start())) {
                        $query->where('start', '>=', $date->start()->toAtomString());
                    }

                    if (!\is_null($date->end())) {
                        $query->where('end', '<=', $date->end()->toAtomString());
                    }

                    return $query;
                }
            )
            ->when(
                !\is_null($criteria->title()),
                fn (Builder $query): Builder => $query->whereLike('title', $criteria->title())
            )
            ->get();

        return $records->map(fn (Record $record): Entity => $this->restoreEntity($record));
    }

    /**
     * {@inheritDoc}
     */
    public function ofUser(UserIdentifier $identifier): Enumerable
    {
        $records = $this->createQuery()
            ->where('user', $identifier->value())
            ->get();

        return $records->map(fn (Record $record): Entity => $this->restoreEntity($record));
    }

    /**
     * {@inheritDoc}
     */
    public function delete(ScheduleIdentifier $identifier): void
    {
        $target = $this->createQuery()
            ->where('identifier', $identifier->value())
            ->first();

        if (\is_null($target)) {
            throw new \OutOfBoundsException(\sprintf('Schedule not found: %s', $identifier->value()));
        }

        $target->delete();
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
            identifier: new ScheduleIdentifier($record->identifier),
            user: new UserIdentifier($record->user),
            customer: $record->customer ? new CustomerIdentifier($record->customer) : null,
            title: $record->title,
            description: $record->description,
            date: $this->restoreDateTimeRange($record),
            status: $this->restoreStatus($record->status),
            repeat: $this->restoreRepeatFrequency($record->repeat),
        );
    }

    /**
     * スケジュールステータスを復元する.
     *
     * @param string $status
     * @return ScheduleStatus
     */
    private function restoreStatus(string $status): ScheduleStatus
    {
        return match ($status) {
            ScheduleStatus::IN_COMPLETE->name => ScheduleStatus::IN_COMPLETE,
            ScheduleStatus::IN_PROGRESS->name => ScheduleStatus::IN_PROGRESS,
            ScheduleStatus::COMPLETED->name => ScheduleStatus::COMPLETED,
        };
    }

    /**
     * 繰り返し頻度を復元する.
     *
     * @param string $repeat
     * @return RepeatFrequency|null
     */
    private function restoreRepeatFrequency(string|null $repeat): RepeatFrequency|null
    {
        if (\is_null($repeat)) {
            return null;
        }

        $candidate = \json_decode($repeat, true);

        $type = match ($candidate->type) {
            FrequencyType::DAILY->name => FrequencyType::DAILY,
            FrequencyType::WEEKLY->name => FrequencyType::WEEKLY,
            FrequencyType::MONTHLY->name => FrequencyType::MONTHLY,
            FrequencyType::YEARLY->name => FrequencyType::YEARLY,
        };

        return new RepeatFrequency(
            type: $type,
            interval: $candidate->interval,
        );
    }
}
