<?php

namespace App\UseCases;

use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\Domains\Schedule\Entities\Schedule as Entity;
use App\Domains\Schedule\ScheduleRepository;
use App\Domains\Schedule\ValueObjects\Criteria;
use App\Domains\Schedule\ValueObjects\FrequencyType;
use App\Domains\Schedule\ValueObjects\RepeatFrequency;
use App\Domains\Schedule\ValueObjects\ScheduleIdentifier;
use App\Domains\Schedule\ValueObjects\ScheduleStatus;
use App\Domains\User\ValueObjects\UserIdentifier;
use App\UseCases\Factories\CommonDomainFactory;
use Illuminate\Support\Enumerable;
use Ramsey\Uuid\Uuid;

/**
 * スケジュールユースケース
 */
class Schedule
{
    public function __construct(
        private readonly ScheduleRepository $repository,
        private readonly CommonDomainFactory $factory
    ) {
    }

    /**
     * スケジュールを永続化する
     *
     * @param string $identifier
     * @param string $user
     * @param string|null $customer
     * @param string $title
     * @param string|null $description
     * @param array $date
     * @param string $status
     * @param array $repeatFrequency
     * @return void
     */
    public function persist(
        string $identifier,
        string $user,
        string|null $customer,
        string $title,
        string|null $description,
        array $date,
        string $status,
        array|null $repeatFrequency
    ): void {
        $customerValue = \is_null($customer) ? Uuid::uuid7()->toString() : $customer;

        $entity = new Entity(
            identifier: new ScheduleIdentifier($identifier),
            user: new UserIdentifier($user),
            customer: \is_null($customer) ? null : new CustomerIdentifier($customerValue),
            title: $title,
            description: $description,
            date: $this->factory->extractDateTimeRange($date),
            status: $this->convertStatus($status),
            repeat: $repeatFrequency ? $this->extractRepeatFrequency($repeatFrequency) : null
        );

        $this->repository->persist($entity);
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
     * ユーザーのスケジュール一覧を取得する
     *
     * @param string $user
     * @return Enumerable<Schedule>
     */
    public function ofUser(string $user): Enumerable
    {
        return $this->repository->ofUser(new UserIdentifier($user));
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
            '1' => ScheduleStatus::IN_COMPLETE,
            '2' => ScheduleStatus::IN_PROGRESS,
            '3' => ScheduleStatus::COMPLETED,
        };
    }

    /**
     * 配列から繰り返し頻度を生成する
     *
     * @param array $repeatFrequency
     * @return RepeatFrequency
     */
    private function extractRepeatFrequency(array $repeatFrequency): RepeatFrequency
    {
        $type = match ($this->factory->extractString($repeatFrequency, 'type')) {
            '1' => FrequencyType::DAILY,
            '2' => FrequencyType::WEEKLY,
            '3' => FrequencyType::MONTHLY,
            '4' => FrequencyType::YEARLY,
        };

        return new RepeatFrequency(
            type: $type,
            interval: $this->factory->extractInteger($repeatFrequency, 'interval')
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
            $this->convertStatus($this->factory->extractString($conditions, 'status')) : null;

        $date = isset($conditions['date']) ?
            $this->factory->extractDateTimeRange($this->factory->extractArray($conditions, 'date')) : null;

        return new Criteria(
            status: $status,
            date: $date,
            title: $this->factory->extractString($conditions, 'title')
        );
    }
}
