<?php

namespace App\Domains\Schedule;

use App\Domains\Schedule\Entities\Schedule;
use App\Domains\Schedule\ValueObjects\Criteria;
use App\Domains\Schedule\ValueObjects\ScheduleIdentifier;
use Illuminate\Support\Enumerable;

/**
 * スケジュールリポジトリ
 */
interface ScheduleRepository
{
    /**
     * スケジュールを追加する
     *
     * @param Schedule $schedule
     * @return void
     *
     * @throws ConflictException スケジュールが重複している場合
     * @throws \InvalidArgumentException スケジュールの各値が不正な場合
     */
    public function add(Schedule $schedule): void;

    /**
     * スケジュールを更新する
     *
     * @param Schedule $schedule
     * @return void
     *
     * @throws \OutOfBoundsException スケジュールが存在しない場合
     */
    public function update(Schedule $schedule): void;

    /**
     * スケジュールを取得する
     *
     * @param ScheduleIdentifier $identifier
     * @return Schedule
     *
     * @throws \OutOfBoundsException スケジュールが存在しない場合
     */
    public function find(ScheduleIdentifier $identifier): Schedule;

    /**
     * スケジュール一覧を取得する
     *
     * @param Criteria $criteria
     * @return Enumerable<Schedule>
     */
    public function list(Criteria $criteria): Enumerable;

    /**
     * スケジュールを削除する
     *
     * @param ScheduleIdentifier $identifier
     * @return void
     *
     * @throws \OutOfBoundsException スケジュールが存在しない場合
     */
    public function delete(ScheduleIdentifier $identifier): void;
}
