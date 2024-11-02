<?php

namespace Tests\Support\Assertions;

use Apollo\Shopping\Domains\Common\ValueObjects\DateTimeRange;

/**
 * 日時範囲の比較を行う機能.
 */
trait DateTimeRangeComparable
{
    /**
     * 2つの日時範囲が同一であること.
     */
    protected function assertSameDateTimeRange(
        ?DateTimeRange $expected,
        ?DateTimeRange $actual
    ): void {
        if (\is_null($expected)) {
            $this->assertNull($actual);

            return;
        }

        $this->assertNotNull($actual);

        if (\is_null($expected->start())) {
            $this->assertNull($actual->start());
        } else {
            $this->assertNotNull($actual->start());
            $this->assertSame($expected->start()->getTimestamp(), $actual->start()->getTimestamp());
        }

        if (\is_null($expected->end())) {
            $this->assertNull($actual->end());
        } else {
            $this->assertNotNull($actual->end());
            $this->assertSame($expected->end()->getTimestamp(), $actual->end()->getTimestamp());
        }
    }
}
