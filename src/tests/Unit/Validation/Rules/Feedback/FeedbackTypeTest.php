<?php

namespace Tests\Unit\Validation\Rules\Feedback;

use App\Validation\Rules\AbstractRule;
use App\Validation\Rules\Feedback\FeedbackType;
use Tests\TestCase;
use Tests\Unit\Validation\Rules\RuleTest;

/**
 * @group unit
 * @group validation
 * @group rules
 * @group feedback
 *
 * @covers coversNothing
 */
class FeedbackTypeTest extends TestCase
{
    use RuleTest;

    /**
     * {@inheritdoc}
     */
    protected function createRule(): AbstractRule
    {
        return new FeedbackType();
    }

    /**
     * {@inheritdoc}
     */
    protected function createValidInput(): array
    {
        return [
          'improvement' => 'IMPROVEMENT',
          'problem' => 'PROBLEM',
          'question' => 'QUESTION',
          'other' => 'OTHER'
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
