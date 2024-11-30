<?php

namespace Tests\Unit\Validation\Rules\Feedback;

use App\Validation\Rules\AbstractRule;
use App\Validation\Rules\Feedback\FeedbackStatus;
use Tests\TestCase;
use Tests\Unit\Validation\Rules\RuleTest;

/**
 * @group unit
 * @group validation
 * @group rules
 * @group feedback
 *
 * @covers \App\Validation\Rules\Feedback\FeedbackStatus
 */
class FeedbackStatusTest extends TestCase
{
    use RuleTest;

    /**
     * {@inheritdoc}
     */
    protected function createRule(): AbstractRule
    {
        return new FeedbackStatus();
    }

    /**
     * {@inheritdoc}
     */
    protected function createValidInput(): array
    {
        return [
          'waiting' => 'WAITING',
          'in_progress' => 'IN_PROGRESS',
          'completed' => 'COMPLETED',
          'not_necessary' => 'NOT_NECESSARY'
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
