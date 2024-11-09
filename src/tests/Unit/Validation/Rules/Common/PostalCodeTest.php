<?php

namespace Tests\Unit\Validation\Rules\Common;

use App\Validation\Rules\AbstractRule;
use App\Validation\Rules\Common\PostalCode;
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
class PostalCodeTest extends TestCase
{
    use RuleTest;

    /**
     * {@inheritdoc}
     */
    protected function createRule(): AbstractRule
    {
        return new PostalCode();
    }

    /**
     * {@inheritdoc}
     */
    protected function createValidInput(): array
    {
        return [
          'fulfilled' => [
            'first' => (string) \mt_rand(100, 999),
            'second' => (string) \mt_rand(1000, 9999)
          ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function createInvalidInput(): array
    {
        return [
          'invalid type' => [
            'first' => \mt_rand(100, 999),
            'second' => \mt_rand(1000, 9999)
          ],
          'no first' => [
            'second' => (string) \mt_rand(1000, 9999)
          ],
          'no second' => [
            'first' => (string) \mt_rand(100, 999)
          ],
          'too short first' => [
            'first' => (string) \mt_rand(0, 99),
            'second' => (string) \mt_rand(1000, 9999)
          ],
          'too short second' => [
            'first' => (string) \mt_rand(100, 999),
            'second' => (string) \mt_rand(0, 999)
          ],
          'too long first' => [
            'first' => (string) \mt_rand(1000, 9999),
            'second' => (string) \mt_rand(1000, 9999)
          ],
          'too long second' => [
            'first' => (string) \mt_rand(100, 999),
            'second' => (string) \mt_rand(10000, 99999)
          ],
          'null' => [
            'first' => null,
            'second' => null
          ]
        ];
    }
}
