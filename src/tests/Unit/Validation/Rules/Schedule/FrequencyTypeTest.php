<?php

namespace Tests\Unit\Validation\Rules\Schedule;

use App\Validation\Rules\AbstractRule;
use App\Validation\Rules\Schedule\FrequencyType;
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
class FrequencyTypeTest extends TestCase
{
    use RuleTest;

    /**
     * {@inheritdoc}
     */
    protected function createRule(): AbstractRule
    {
        return new FrequencyType();
    }

    /**
     * {@inheritdoc}
     */
    protected function createValidInput(): array
    {
        return [
          'daily' => 'DAILY',
          'weekly' => 'WEEKLY',
          'monthly' => 'MONTHLY',
          'yearly' => 'YEARLY'
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
