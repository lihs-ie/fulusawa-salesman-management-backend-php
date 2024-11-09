<?php

namespace Tests\Unit\Http\Encoders\Schedule;

use App\Domains\Schedule\Entities\Schedule;
use App\Domains\Schedule\ValueObjects\RepeatFrequency;
use App\Http\Encoders\Schedule\ScheduleEncoder;
use Tests\Support\Assertions\NullableValueComparable;
use Tests\Support\DependencyBuildable;
use Tests\TestCase;

/**
 * @group unit
 * @group http
 * @group encoders
 * @group schedule
 *
 * @coversNothing
 */
class ScheduleEncoderTest extends TestCase
{
    use DependencyBuildable;
    use NullableValueComparable;

    /**
     * @testdox testInstantiateSuccess 正常な値によってインスタンスを生成できること.
     */
    public function testInstantiateSuccess(): void
    {
        $encoder = new ScheduleEncoder();

        $this->assertInstanceOf(ScheduleEncoder::class, $encoder);
    }

    /**
     * @testdox testEncodeSuccessReturnsArray encodeメソッドでスケジュールをJSONエンコード可能な形式に変換できること.
     */
    public function testEncodeSuccessReturnsArray(): void
    {
        $schedule = $this->builder()->create(Schedule::class);

        $encoder = new ScheduleEncoder();

        $actual = $encoder->encode($schedule);

        $this->assertIsArray($actual);
        $this->assertSame($schedule->identifier()->value(), $actual['identifier']);
        $this->assertSame($schedule->user()->value(), $actual['user']);
        $this->assertSame($schedule->customer()?->value(), $actual['customer']);
        $this->assertSame($schedule->title(), $actual['title']);
        $this->assertSame($schedule->description(), $actual['description']);
        $this->assertSame($schedule->date()->start()->toAtomString(), $actual['date']['start']);
        $this->assertSame($schedule->date()->end()->toAtomString(), $actual['date']['end']);
        $this->assertSame($schedule->status()->name, $actual['status']);
        $this->assertNullOr(
            $schedule->repeat(),
            $actual['repeatFrequency'],
            function (RepeatFrequency $expected, array $actual) {
                $this->assertSame($expected->type()->name, $actual['type']);
                $this->assertSame($expected->interval(), $actual['interval']);
            }
        );
    }
}
