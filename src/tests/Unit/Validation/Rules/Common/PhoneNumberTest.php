<?php

namespace Tests\Unit\Validation\Rules\Common;

use App\Validation\Rules\AbstractRule;
use App\Validation\Rules\Common\PhoneNumber;
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
class PhoneNumberTest extends TestCase
{
    use RuleTest;

    /**
     * {@inheritdoc}
     */
    protected function createRule(): AbstractRule
    {
        return new PhoneNumber();
    }

    /**
     * {@inheritdoc}
     */
    protected function createValidInput(): array
    {
        return [
          'fulfilled' => [
            'areaCode' => $this->createValidAreaCode(),
            'localCode' => (string) \mt_rand(0, 9999),
            'subscriberNumber' => (string) \mt_rand(100, 99999)
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
            'areaCode' => \mt_rand(0, 999),
            'localCode' => \mt_rand(0, 9999),
            'subscriberNumber' => \mt_rand(100, 99999)
          ],
          'no areaCode' => [
            'localCode' => (string) \mt_rand(0, 9999),
            'subscriberNumber' => (string) \mt_rand(100, 99999)
          ],
          'no localCode' => [
            'areaCode' => $this->createValidAreaCode(),
            'subscriberNumber' => (string) \mt_rand(100, 99999)
          ],
          'no subscriberNumber' => [
            'areaCode' => $this->createValidAreaCode(),
            'localCode' => (string) \mt_rand(0, 9999)
          ],
          'empty' => [],
          'too long areaCode' => [
            'areaCode' => (string) \mt_rand(10000, 99999),
            'localCode' => (string) \mt_rand(0, 9999),
            'subscriberNumber' => (string) \mt_rand(100, 99999)
          ],
          'too long localCode' => [
            'areaCode' => $this->createValidAreaCode(),
            'localCode' => (string) \mt_rand(10000, 99999),
            'subscriberNumber' => (string) \mt_rand(100, 99999)
          ],
          'too long subscriberNumber' => [
            'areaCode' => $this->createValidAreaCode(),
            'localCode' => (string) \mt_rand(0, 9999),
            'subscriberNumber' => (string) \mt_rand(100000, 999999)
          ],
          'null' => [
            'areaCode' => null,
            'localCode' => null,
            'subscriberNumber' => null
          ]
        ];
    }

    /**
     * 正しいエリアコードを生成する.
     */
    private function createValidAreaCode(): string
    {
        return '0' . (string) \mt_rand(0, 999);
    }
}
