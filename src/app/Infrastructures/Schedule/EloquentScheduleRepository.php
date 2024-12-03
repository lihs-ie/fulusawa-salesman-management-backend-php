<?php

namespace App\Infrastructures\Schedule;

use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\Domains\Schedule\Entities\Schedule as Entity;
use App\Domains\Schedule\ScheduleRepository;
use App\Domains\Schedule\ValueObjects\Criteria;
use App\Domains\Schedule\ValueObjects\FrequencyType;
use App\Domains\Schedule\ValueObjects\RepeatFrequency;
use App\Domains\Schedule\ValueObjects\ScheduleContent;
use App\Domains\Schedule\ValueObjects\ScheduleIdentifier;
use App\Domains\Schedule\ValueObjects\ScheduleStatus;
use App\Domains\User\ValueObjects\UserIdentifier;
use App\Infrastructures\Common\AbstractEloquentRepository;
use App\Infrastructures\Schedule\Models\Schedule as Record;
use App\Infrastructures\Support\Common\EloquentCommonDomainRestorer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;

/**
 * スケジュールリポジトリ
 */
class EloquentScheduleRepository extends AbstractEloquentRepository implements ScheduleRepository
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
    public function add(Entity $schedule): void
    {
        $repeat = \is_null($schedule->repeat()) ? null : \json_encode([
            'type' => $schedule->repeat()->type()->name,
            'interval' => $schedule->repeat()->interval(),
        ]);

        try {
            $this->createQuery()
                ->create([
                    'identifier' => $schedule->identifier()->value(),
                    'participants' => $schedule->participants()
                        ->map
                        ->value()
                        ->toJson(),
                    'creator' => $schedule->creator()->value(),
                    'updater' => $schedule->updater()->value(),
                    'customer' => $schedule->customer()?->value(),
                    'title' => $schedule->content()->title(),
                    'description' => $schedule->content()->description(),
                    'start' => $schedule->date()->start()->toAtomString(),
                    'end' => $schedule->date()->end()->toAtomString(),
                    'status' => $schedule->status()->name,
                    'repeat' => $repeat,
                ]);
        } catch (\PDOException $exception) {
            $this->handlePDOException($exception, $schedule->identifier()->value());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function update(Entity $schedule): void
    {
        $target = $this->createQuery()
            ->ofIdentifier($schedule->identifier())
            ->first();

        if (\is_null($target)) {
            throw new \OutOfBoundsException(\sprintf('Schedule not found: %s', $schedule->identifier()->value()));
        }

        $repeat = \is_null($schedule->repeat()) ? null : \json_encode([
            'type' => $schedule->repeat()->type()->name,
            'interval' => $schedule->repeat()->interval(),
        ]);

        try {
            $target->update([
                'participants' => $schedule->participants()
                    ->map
                    ->value()
                    ->toJson(),
                'creator' => $schedule->creator()->value(),
                'updater' => $schedule->updater()->value(),
                'customer' => $schedule->customer()?->value(),
                'title' => $schedule->content()->title(),
                'description' => $schedule->content()->description(),
                'start' => $schedule->date()->start()->toAtomString(),
                'end' => $schedule->date()->end()->toAtomString(),
                'status' => $schedule->status()->name,
                'repeat' => $repeat,
            ]);
        } catch (\PDOException $exception) {
            $this->handlePDOException($exception, $schedule->identifier()->value());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function find(ScheduleIdentifier $identifier): Entity
    {
        $record = $this->createQuery()
            ->ofIdentifier($identifier)
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
            ->ofCriteria($criteria)
            ->get();

        return $records->map(fn (Record $record): Entity => $this->restoreEntity($record));
    }

    /**
     * {@inheritDoc}
     */
    public function delete(ScheduleIdentifier $identifier): void
    {
        $target = $this->createQuery()
            ->ofIdentifier($identifier)
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
            participants: $this->restoreParticipants($record),
            creator: new UserIdentifier($record->creator),
            updater: new UserIdentifier($record->updater),
            customer: $record->customer ? new CustomerIdentifier($record->customer) : null,
            content: $this->restoreContent($record),
            date: $this->restoreDateTimeRange($record),
            status: $this->restoreStatus($record->status),
            repeat: $this->restoreRepeatFrequency($record->repeat),
        );
    }

    /**
     * レコードから参加者リストを復元する.
     */
    private function restoreParticipants(Record $record): Enumerable
    {
        return Collection::make(\json_decode($record->participants, true))
            ->map(fn (string $value): UserIdentifier => new UserIdentifier($value));
    }

    /**
     * レコードからスケジュール内容を復元する.
     */
    private function restoreContent(Record $record): ScheduleContent
    {
        return new ScheduleContent(
            title: $record->title,
            description: $record->description,
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
