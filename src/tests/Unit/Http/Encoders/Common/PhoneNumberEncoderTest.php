<?php

namespace Tests\Unit\Http\Encoders\Common;

use App\Domains\Common\ValueObjects\Address;
use App\Domains\Common\ValueObjects\PhoneNumber;
use App\Http\Encoders\Common\AddressEncoder;
use App\Http\Encoders\Common\PhoneNumberEncoder;
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
class PhoneNumberEncoderTest extends TestCase
{
    use DependencyBuildable;

    /**
     * @testdox testInstantiateSuccess インスタンスが生成できること.
     */
    public function testInstantiateSuccess(): void
    {
        $encoder = new PhoneNumberEncoder();

        $this->assertInstanceOf(PhoneNumberEncoder::class, $encoder);
    }

    /**
     * @testdox testEncodeSuccess encodeメソッドに電話番号を与えたときJSONエンコード可能な形式に変換できること.
     */
    public function testEncodeSuccess(): void
    {
        $phone = $this->builder()->create(PhoneNumber::class);

        $encoder = new PhoneNumberEncoder();

        $actual = $encoder->encode($phone);
        $this->assertIsArray($actual);

        $this->assertSame($phone->areaCode(), $actual['areaCode']);
        $this->assertSame($phone->localCode(), $actual['localCode']);
        $this->assertSame($phone->subscriberNumber(), $actual['subscriberNumber']);
    }
}
