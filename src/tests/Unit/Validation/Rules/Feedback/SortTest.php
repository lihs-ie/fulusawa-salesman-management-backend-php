<?php

namespace Tests\Unit\Validation\Rules\Feedback;

use App\Validation\Rules\AbstractRule;
use App\Validation\Rules\Feedback\Sort;
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
class SortTest extends TestCase
{
    use RuleTest;

    /**
     * {@inheritdoc}
     */
    protected function createRule(): AbstractRule
    {
        return new Sort();
    }

    /**
     * {@inheritdoc}
     */
    protected function createValidInput(): array
    {
        return [
          'created_at_desc' => 'CREATED_AT_DESC',
          'created_at_asc' => 'CREATED_AT_ASC',
          'updated_at_desc' => 'UPDATED_AT_DESC',
          'updated_at_asc' => 'UPDATED_AT_ASC'
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
