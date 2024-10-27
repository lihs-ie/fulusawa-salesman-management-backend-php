<?php

namespace App\Domains\Schedule;

use App\Domains\Schedule\Entities\Schedule;
use App\Domains\Schedule\ValueObjects\Criteria;
use App\Domains\Schedule\ValueObjects\ScheduleIdentifier;
use App\Domains\User\ValueObjects\UserIdentifier;
use Illuminate\Support\Enumerable;

/**
 * スケジュールリポジトリ
 */
interface ScheduleRepository
{
    public function persist(Schedule $schedule): void;

    public function find(ScheduleIdentifier $identifier): Schedule;

    public function list(Criteria $criteria): Enumerable;

    public function ofUser(UserIdentifier $user): Enumerable;

    public function delete(ScheduleIdentifier $identifier): void;
}
