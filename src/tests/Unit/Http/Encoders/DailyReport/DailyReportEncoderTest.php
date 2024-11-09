<?php

namespace Tests\Unit\Http\Encoders\DailyReport;

use App\Domains\DailyReport\Entities\DailyReport;
use App\Domains\Schedule\ValueObjects\ScheduleIdentifier;
use App\Domains\Visit\ValueObjects\VisitIdentifier;
use App\Http\Encoders\DailyReport\DailyReportEncoder;
use Tests\Support\DependencyBuildable;
use Tests\TestCase;

/**
 * @group unit
 * @group http
 * @group encoders
 * @group dailyreport
 *
 * @coversNothing
 */
class DailyReportEncoderTest extends TestCase
{
    use DependencyBuildable;

    /**
     * @testdox testInstantiateSuccess 正常な値によってインスタンスを生成できること.
     */
    public function testInstantiateSuccess(): void
    {
        $encoder = new DailyReportEncoder();

        $this->assertInstanceOf(DailyReportEncoder::class, $encoder);
    }

    /**
     * @testdox testEncodeSuccessReturnsArray encodeメソッドで日報をエンコードできること.
     */
    public function testEncodeSuccessReturnsArray(): void
    {
        $encoder = new DailyReportEncoder();

        $dailyReport = $this->builder()->create(DailyReport::class);

        $actual = $encoder->encode($dailyReport);

        $this->assertIsArray($actual);
        $this->assertSame($dailyReport->identifier()->value(), $actual['identifier']);
        $this->assertSame($dailyReport->user()->value(), $actual['user']);
        $this->assertSame($dailyReport->date()->toAtomString(), $actual['date']);
        $this->assertSame(
            $dailyReport->schedules()
            ->map(fn (ScheduleIdentifier $schedule): string => $schedule->value())
            ->all(),
            $actual['schedules']
        );
        $this->assertSame(
            $dailyReport->visits()
            ->map(fn (VisitIdentifier $visit): string => $visit->value())
            ->all(),
            $actual['visits']
        );
        $this->assertSame($dailyReport->isSubmitted(), $actual['isSubmitted']);
    }
}
