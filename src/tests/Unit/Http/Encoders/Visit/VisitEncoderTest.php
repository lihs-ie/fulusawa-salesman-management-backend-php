<?php

namespace Tests\Unit\Http\Encoders\Visit;

use App\Domains\Common\ValueObjects\PhoneNumber;
use App\Domains\Visit\Entities\Visit;
use App\Http\Encoders\Common\AddressEncoder;
use App\Http\Encoders\Common\PhoneNumberEncoder;
use App\Http\Encoders\Visit\VisitEncoder;
use Illuminate\Support\Str;
use Tests\Support\Assertions\NullableValueComparable;
use Tests\Support\DependencyBuildable;
use Tests\TestCase;

/**
 * @group unit
 * @group http
 * @group encoders
 * @group visit
 *
 * @coversNothing
 */
class VisitEncoderTest extends TestCase
{
    use DependencyBuildable;
    use NullableValueComparable;

    /**
     * @testdox testInstantiateSuccess 正常な値によってインスタンスを生成できること.
     */
    public function testInstantiateSuccess(): void
    {
        $encoder = new VisitEncoder(
            addressEncoder: $this->builder()->create(AddressEncoder::class),
            phoneEncoder: $this->builder()->create(PhoneNumberEncoder::class)
        );

        $this->assertInstanceOf(VisitEncoder::class, $encoder);
    }

    /**
     * @testdox testEncodeSuccessReturnsArray 正常な値によってエンコードできること.
     * @dataProvider provideVisitValues
     */
    public function testEncodeSuccessReturnsArray(array $overrides): void
    {
        $visit = $this->builder()->create(Visit::class, null, $overrides);

        $addressEncoder = $this->builder()->create(AddressEncoder::class);
        $phoneEncoder = $this->builder()->create(PhoneNumberEncoder::class);

        $encoder = new VisitEncoder(
            addressEncoder: $addressEncoder,
            phoneEncoder: $phoneEncoder
        );

        $encoded = $encoder->encode($visit);

        $this->assertIsArray($encoded);
        $this->assertSame($visit->identifier()->value(), $encoded['identifier']);
        $this->assertSame($visit->user()->value(), $encoded['user']);
        $this->assertSame($visit->visitedAt()->toAtomString(), $encoded['visitedAt']);
        $this->assertSame($addressEncoder->encode($visit->address()), $encoded['address']);
        $this->assertNullOr(
            $visit->phone(),
            $encoded['phone'],
            function (PhoneNumber $expected, array $actual) use ($phoneEncoder) {
                $this->assertSame($phoneEncoder->encode($expected), $actual);
            }
        );
        $this->assertSame($visit->hasGraveyard(), $encoded['hasGraveyard']);
        $this->assertSame($visit->note(), $encoded['note']);
        $this->assertSame($visit->result()->name, $encoded['result']);
    }

    /**
     *
     */
    public static function provideVisitValues(): \Generator
    {
        yield 'has phone' => [[
          'phone' => new PhoneNumber(
              areaCode: '03',
              localCode: '1234',
              subscriberNumber: '5678'
          )
        ]];

        yield 'no phone' => [[
          'phone' => null
        ]];

        yield 'has note' => [[
          'note' => Str::random(\mt_rand(1, 1000))
        ]];

        yield 'no note' => [[
          'note' => null
        ]];
    }
}
