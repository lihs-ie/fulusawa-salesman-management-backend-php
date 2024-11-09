<?php

namespace Tests\Unit\Validation\Rules\Visit;

use App\Validation\Rules\AbstractRule;
use App\Validation\Rules\Visit\VisitResult;
use Tests\TestCase;
use Tests\Unit\Validation\Rules\RuleTest;

/**
 * @group unit
 * @group validation
 * @group rules
 * @group visit
 *
 * @coversNothing
 */
class VisitResultTest extends TestCase
{
    use RuleTest;

    /**
     * {@inheritdoc}
     */
    protected function createRule(): AbstractRule
    {
        return new VisitResult();
    }

    /**
     * {@inheritdoc}
     */
    protected function createValidInput(): array
    {
        return [
          'no_contact' => 'NO_CONTRACT',
          'contract' => 'CONTRACT',
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
