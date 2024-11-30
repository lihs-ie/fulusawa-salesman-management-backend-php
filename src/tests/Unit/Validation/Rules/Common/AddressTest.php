<?php

namespace Tests\Unit\Validation\Rules\Common;

use App\Domains\Common\ValueObjects\Prefecture;
use App\Validation\Rules\AbstractRule;
use App\Validation\Rules\Common\Address;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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
class AddressTest extends TestCase
{
    use RuleTest;

    /**
     * {@inheritdoc}
     */
    protected function createRule(): AbstractRule
    {
        return new Address();
    }

    /**
     * {@inheritdoc}
     */
    protected function createValidInput(): array
    {
        return [
          'fulfilled' => [
            'prefecture' => $this->createValidPrefecture(),
            'city' => Str::random(),
            'street' => Str::random(),
            'building' => Str::random(),
            'postalCode' => $this->createValidPostalCode(),
          ],
          'building is null' => [
            'prefecture' => $this->createValidPrefecture(),
            'city' => Str::random(),
            'street' => Str::random(),
            'building' => null,
            'postalCode' => $this->createValidPostalCode(),
          ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function createInvalidInput(): array
    {
        $validPrefecture = $this->createValidPrefecture();
        $validPostalCode = $this->createValidPostalCode();

        return [
          'invalid type' => [
            'prefecture' => \mt_rand(1, 47),
            'city' => Str::random(),
            'street' => Str::random(),
            'building' => Str::random(),
            'postalCode' => [
              'first' => \mt_rand(100, 999),
              'second' => \mt_rand(1000, 9999)
            ],
            'phoneNumber' => [
              'areaCode' => \mt_rand(0, 999),
              'localCode' => \mt_rand(0, 9999),
              'subscriberNumber' => \mt_rand(100, 99999)
            ]
          ],
          'no prefecture' => [
            'city' => Str::random(),
            'street' => Str::random(),
            'building' => Str::random(),
            'postalCode' => $validPostalCode,
          ],
          'no city' => [
            'prefecture' => $validPrefecture,
            'street' => Str::random(),
            'building' => Str::random(),
            'postalCode' => $validPostalCode,
          ],
          'no street' => [
            'prefecture' => $validPrefecture,
            'city' => Str::random(),
            'building' => Str::random(),
            'postalCode' => $validPostalCode,
          ],
          'no building' => [
            'prefecture' => $validPrefecture,
            'city' => Str::random(),
            'street' => Str::random(),
            'postalCode' => $validPostalCode,
          ],
          'no postalCode' => [
            'prefecture' => $validPrefecture,
            'city' => Str::random(),
            'street' => Str::random(),
            'building' => Str::random(),
          ],
          'no phoneNumber' => [
            'prefecture' => $validPrefecture,
            'city' => Str::random(),
            'street' => Str::random(),
            'building' => Str::random(),
            'postalCode' => $validPostalCode
          ],
        ];
    }

    /**
     * 正しい都道府県の値を生成する.
     */
    private function createValidPrefecture(): string
    {
        return (string)Collection::make(Prefecture::cases())->random()->value;
    }

    /**
     * 正しい郵便番号の値を生成する.
     */
    private function createValidPostalCode(): array
    {
        return [
          'first' => (string)mt_rand(100, 999),
          'second' => (string)mt_rand(1000, 9999)
        ];
    }
}
