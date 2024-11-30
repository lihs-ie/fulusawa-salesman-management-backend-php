<?php

namespace Tests\Unit\Http\Encoders\Common;

use App\Domains\Common\ValueObjects\Address;
use App\Http\Encoders\Common\AddressEncoder;
use Tests\Support\DependencyBuildable;
use Tests\TestCase;

/**
 * @group unit
 * @group http
 * @group encoders
 * @group common
 *
 * @coversNothing
 */
class AddressEncoderTest extends TestCase
{
    use DependencyBuildable;

    /**
     * @testdox testInstantiateSuccess インスタンスが生成できること.
     */
    public function testInstantiateSuccess(): void
    {
        $encoder = new AddressEncoder();

        $this->assertInstanceOf(AddressEncoder::class, $encoder);
    }

    /**
     * @testdox testEncodeSuccess encodeメソッドに住所を与えたときJSONエンコード可能な形式に変換できること.
     */
    public function testEncodeSuccess(): void
    {
        $address = $this->builder()->create(Address::class);

        $encoder = new AddressEncoder();

        $actual = $encoder->encode($address);
        $this->assertIsArray($actual);

        $this->assertSame($address->postalCode()->first(), $actual['postalCode']['first']);
        $this->assertSame($address->postalCode()->second(), $actual['postalCode']['second']);
        $this->assertSame($address->prefecture()->value, $actual['prefecture']);
        $this->assertSame($address->city(), $actual['city']);
        $this->assertSame($address->street(), $actual['street']);
        $this->assertSame($address->building(), $actual['building']);
    }
}
