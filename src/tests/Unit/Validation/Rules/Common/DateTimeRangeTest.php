<?php

namespace Tests\Unit\Validation\Rules\Common;

use App\Validation\Rules\AbstractRule;
use App\Validation\Rules\Common\DateTimeRange;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Validation\Rules\RuleTest;

/**
 * @group unit
 * @group validation
 * @group rules
 *
 * @internal
 * @coversNothing
 */
class DateTimeRangeTest extends TestCase
{
    use RuleTest;

    /**
     * {@inheritdoc}
     */
    protected function createRule(): AbstractRule
    {
        return new DateTimeRange();
    }

    /**
     * {@inheritdoc}
     */
    protected function createValidInput(): array
    {
        return [
            'empty' => [
                'start' => null,
                'end' => null,
            ],
            'only start' => [
                'start' => CarbonImmutable::now()->format(\DATE_ATOM),
                'end' => null,
            ],
            'only end' => [
                'start' => null,
                'end' => CarbonImmutable::now()->format(\DATE_ATOM),
            ],
            'fulfilled' => [
                'start' => CarbonImmutable::now()->format(\DATE_ATOM),
                'end' => CarbonImmutable::now()->format(\DATE_ATOM),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function createInvalidInput(): array
    {
        return [
            'invalid type' => Str::random(\mt_rand(1, 64)),
            'no start' => ['end' => null],
            'no end' => ['start' => null],
            'invalid type of start' => [
                'start' => \mt_rand(1, 10),
                'end' => null,
            ],
            'invalid type of end' => [
                'start' => null,
                'end' => \mt_rand(1, 10),
            ],
            'invalid format of start' => [
                'start' => \date('Y-m-d H:i:s'),
                'end' => null,
            ],
            'invalid format of end' => [
                'start' => null,
                'end' => \date('Y-m-d H:i:s'),
            ],
        ];
    }
}
