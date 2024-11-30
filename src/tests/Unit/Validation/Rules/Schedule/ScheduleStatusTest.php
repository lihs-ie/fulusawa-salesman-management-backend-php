<?php

namespace Tests\Unit\Validation\Rules\Schedule;

use App\Validation\Rules\AbstractRule;
use App\Validation\Rules\Schedule\ScheduleStatus;
use Tests\TestCase;
use Tests\Unit\Validation\Rules\RuleTest;

/**
 * @group unit
 * @group validation
 * @group rules
 * @group schedule
 *
 * @coversNothing
 */
class ScheduleStatusTest extends TestCase
{
    use RuleTest;

    /**
     * {@inheritdoc}
     */
    protected function createRule(): AbstractRule
    {
        return new ScheduleStatus();
    }

    /**
     * {@inheritdoc}
     */
    protected function createValidInput(): array
    {
        return [
          'in_complete' => 'IN_COMPLETE',
          'in_progress' => 'IN_PROGRESS',
          'completed' => 'COMPLETED',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function createInvalidInput(): array
    {
        return [
          'invalid status' => \mt_rand(1, 255),
          'empty' => '',
          'null' => null
        ];
    }
}
