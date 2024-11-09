<?php

namespace Tests\Unit\Validation\Rules\Common;

use App\Validation\Rules\AbstractRule;
use App\Validation\Rules\Common\Prefecture;
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
class PrefectureTest extends TestCase
{
    use RuleTest;

    /**
     * {@inheritdoc}
     */
    protected function createRule(): AbstractRule
    {
        return new Prefecture();
    }

    /**
     * {@inheritdoc}
     */
    protected function createValidInput(): array
    {
        return [
          'valid' => (string) \mt_rand(1, 47)
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function createInvalidInput(): array
    {
        return [
          'invalid type' => \mt_rand(1, 47),
          'too small' => (string) \mt_rand(0, 0),
          'too large' => (string) \mt_rand(48, 100)
        ];
    }
}
