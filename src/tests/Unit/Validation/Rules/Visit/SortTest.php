<?php

namespace Tests\Unit\Validation\Rules\Visit;

use App\Validation\Rules\AbstractRule;
use App\Validation\Rules\Visit\Sort;
use Tests\TestCase;
use Tests\Unit\Validation\Rules\RuleTest;

/**
 * @group unit
 * @group validation
 * @group rules
 * @group visit
 *
 * @covers \coversNothing
 *
 * @internal
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
            'visited_at_asc' => 'VISITED_AT_ASC',
            'visited_at_desc' => 'VISITED_AT_DESC',
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
            'null' => null,
        ];
    }
}
