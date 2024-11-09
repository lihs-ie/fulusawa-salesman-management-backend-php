<?php

namespace Tests\Unit\Validation\Rules\Cemetery;

use App\Validation\Rules\AbstractRule;
use App\Validation\Rules\Cemetery\CemeteryType;
use Tests\TestCase;
use Tests\Unit\Validation\Rules\RuleTest;

/**
 * @group unit
 * @group validation
 * @group rules
 * @group common
 *
 * @coversNothing
 */
class CemeteryTypeTest extends TestCase
{
    use RuleTest;

    /**
     * {@inheritdoc}
     */
    protected function createRule(): AbstractRule
    {
        return new CemeteryType();
    }

    /**
     * {@inheritdoc}
     */
    protected function createValidInput(): array
    {
        return [
          'individual' => 'INDIVIDUAL',
          'family' => 'FAMILY',
          'community' => 'COMMUNITY',
          'other' => 'OTHER'
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function createInvalidInput(): array
    {
        return [
          'invalid type' => \mt_rand(1, 255),
          'empty' => '',
          'null' => null
        ];
    }
}
