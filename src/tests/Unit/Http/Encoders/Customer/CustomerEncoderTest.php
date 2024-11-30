<?php

namespace Tests\Unit\Http\Encoders\Customer;

use App\Domains\Cemetery\ValueObjects\CemeteryIdentifier;
use App\Domains\Customer\Entities\Customer;
use App\Domains\TransactionHistory\ValueObjects\TransactionHistoryIdentifier;
use App\Http\Encoders\Common\AddressEncoder;
use App\Http\Encoders\Common\PhoneNumberEncoder;
use App\Http\Encoders\Customer\CustomerEncoder;
use Illuminate\Support\Collection;
use Tests\Support\DependencyBuildable;
use Tests\TestCase;

/**
 * @group unit
 * @group http
 * @group encoders
 * @group customer
 *
 * @coversNothing
 */
class CustomerEncoderTest extends TestCase
{
    use DependencyBuildable;

    /**
     * @testdox testInstantiateSuccess インスタンスが生成できること.
     */
    public function testInstantiateSuccess(): void
    {
        $encoder = new CustomerEncoder(
            addressEncoder: $this->builder()->create(AddressEncoder::class),
            phoneEncoder: $this->builder()->create(PhoneNumberEncoder::class)
        );

        $this->assertInstanceOf(CustomerEncoder::class, $encoder);
    }

    /**
     * @testdox testEncodeSuccess encodeメソッドに住所を与えたときJSONエンコード可能な形式に変換できること.
     */
    public function testEncodeSuccess(): void
    {
        $customer = $this->builder()->create(Customer::class);

        $addressEncoder = $this->builder()->create(AddressEncoder::class);
        $phoneEncoder = $this->builder()->create(PhoneNumberEncoder::class);

        $encoder = new CustomerEncoder(
            addressEncoder: $addressEncoder,
            phoneEncoder: $phoneEncoder
        );

        $actual = $encoder->encode($customer);
        $this->assertIsArray($actual);

        $this->assertSame($customer->identifier()->value(), $actual['identifier']);
        $this->assertSame($customer->firstName(), $actual['name']['first']);
        $this->assertSame($customer->lastName(), $actual['name']['last']);
        $this->assertSame($addressEncoder->encode($customer->address()), $actual['address']);
        $this->assertSame($phoneEncoder->encode($customer->phone()), $actual['phone']);

        $customer->cemeteries()
          ->zip(Collection::make($actual['cemeteries']))
          ->eachSpread(function (?CemeteryIdentifier $expected, $actual): void {
              $this->assertNotNull($expected);
              $this->assertNotNull($actual);
              $this->assertSame($expected->value(), $actual);
          })
        ;

        $customer->transactionHistories()
          ->zip(Collection::make($actual['transactionHistories']))
          ->eachSpread(function (?TransactionHistoryIdentifier $expected, $actual): void {
              $this->assertNotNull($expected);
              $this->assertNotNull($actual);
              $this->assertSame($expected->value(), $actual);
          });
    }
}
