<?php

namespace App\UseCases;

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
use App\UseCases\Factories\CommonDomainFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;

/**
 * スケジュールユースケース
 */
class Schedule
{
    use CommonDomainFactory;

    public function __construct(
        private readonly ScheduleRepository $repository,
    ) {
    }

    /**
     * スケジュールを永続化する
     *
     * @param string $identifier
     * @param array $participants
     * @param string $creator
     * @param string $updater
     * @param string|null $customer
     * @param array $content
     * @param array $date
     * @param string $status
     * @param array $repeatFrequency
     * @return void
     */
    public function add(
        string $identifier,
        array $participants,
        string $creator,
        string $updater,
        string|null $customer,
        array $content,
        array $date,
        string $status,
        array|null $repeatFrequency
    ): void {
        $entity = new Entity(
            identifier: new ScheduleIdentifier($identifier),
            participants: $this->extractParticipants($participants),
            creator: new UserIdentifier($creator),
            updater: new UserIdentifier($updater),
            customer: \is_null($customer) ? null : new CustomerIdentifier($customer),
            content: $this->extractScheduleContent($content),
            date: $this->extractDateTimeRange($date),
            status: $this->convertStatus($status),
            repeat: $repeatFrequency ? $this->extractRepeatFrequency($repeatFrequency) : null
        );

        $this->repository->add($entity);
    }

    /**
     * スケジュールを更新する
     *
     * @param string $identifier
     * @param array $participants
     * @param string $creator
     * @param string $updater
     * @param string|null $customer
     * @param array $content
     * @param array $date
     * @param string $status
     * @param array|null $repeatFrequency
     * @return void
     */
    public function update(
        string $identifier,
        array $participants,
        string $creator,
        string $updater,
        string|null $customer,
        array $content,
        array $date,
        string $status,
        array|null $repeatFrequency
    ): void {
        $entity = new Entity(
            identifier: new ScheduleIdentifier($identifier),
            participants: $this->extractParticipants($participants),
            creator: new UserIdentifier($creator),
            updater: new UserIdentifier($updater),
            customer: \is_null($customer) ? null : new CustomerIdentifier($customer),
            content: $this->extractScheduleContent($content),
            date: $this->extractDateTimeRange($date),
            status: $this->convertStatus($status),
            repeat: $repeatFrequency ? $this->extractRepeatFrequency($repeatFrequency) : null
        );

        $this->repository->update($entity);
    }

    /**
     * スケジュールを取得する
     *
     * @param string $identifier
     * @return Entity
     */
    public function find(string $identifier): Entity
    {
        return $this->repository->find(new ScheduleIdentifier($identifier));
    }

    /**
     * スケジュール一覧を取得する
     *
     * @param array $conditions
     * @return Enumerable<Schedule>
     */
    public function list(array $conditions): Enumerable
    {
        return $this->repository->list($this->createCriteria($conditions));
    }

    /**
     * スケジュールを削除する
     *
     * @param string $identifier
     * @return void
     */
    public function delete(string $identifier): void
    {
        $this->repository->delete(new ScheduleIdentifier($identifier));
    }

    /**
     * 文字列からスケジュールステータスを生成する
     *
     * @param string $status
     * @return ScheduleStatus
     */
    private function convertStatus(string $status): ScheduleStatus
    {
        return match ($status) {
            ScheduleStatus::IN_COMPLETE->name => ScheduleStatus::IN_COMPLETE,
            ScheduleStatus::IN_PROGRESS->name => ScheduleStatus::IN_PROGRESS,
            ScheduleStatus::COMPLETED->name => ScheduleStatus::COMPLETED,
        };
    }

    /**
     * 配列から参加者リストを生成する
     *
     * @param array $participants
     * @return Enumerable
     */
    private function extractParticipants(array $participants): Enumerable
    {
        return Collection::make($participants)
            ->map(fn (string $participant): UserIdentifier => new UserIdentifier($participant));
    }

    /**
     * 配列からスケジュール内容を生成する
     *
     * @param array $content
     * @return ScheduleContent
     */
    private function extractScheduleContent(array $content): ScheduleContent
    {
        return new ScheduleContent(
            title: $this->extractString($content, 'title'),
            description: $this->extractString($content, 'description')
        );
    }

    /**
     * 配列から繰り返し頻度を生成する
     *
     * @param array $repeatFrequency
     * @return RepeatFrequency
     */
    private function extractRepeatFrequency(array $repeatFrequency): RepeatFrequency
    {
        $type = match ($this->extractString($repeatFrequency, 'type')) {
            FrequencyType::DAILY->name => FrequencyType::DAILY,
            FrequencyType::WEEKLY->name => FrequencyType::WEEKLY,
            FrequencyType::MONTHLY->name => FrequencyType::MONTHLY,
            FrequencyType::YEARLY->name => FrequencyType::YEARLY,
        };

        return new RepeatFrequency(
            type: $type,
            interval: $this->extractInteger($repeatFrequency, 'interval')
        );
    }

    /**
     * 配列から検索条件を生成する
     *
     * @param array $conditions
     * @return Criteria
     */
    private function createCriteria(array $conditions): Criteria
    {
        $status = isset($conditions['status']) ?
            $this->convertStatus($this->extractString($conditions, 'status')) : null;

        $date = isset($conditions['date']) ?
            $this->extractDateTimeRange($this->extractArray($conditions, 'date')) : null;

        $user = isset($conditions['user']) ?
            new UserIdentifier($this->extractString($conditions, 'user')) : null;

        return new Criteria(
            status: $status,
            date: $date,
            title: $this->extractString($conditions, 'title'),
            user: $user
        );
    }
}
