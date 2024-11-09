<?php

namespace Tests\Unit\Validation\Rules\Schedule;

use App\Domains\Schedule\ValueObjects\FrequencyType;
use App\Validation\Rules\AbstractRule;
use App\Validation\Rules\Schedule\RepeatFrequency;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Tests\Unit\Validation\Rules\RuleTest;

/**
 * @group unit
 * @group validation
 * @group rules
 * @group schedule
 *
 * @coversNothing
 */
class RepeatFrequencyTest extends TestCase
{
    use RuleTest;

    /**
     * {@inheritdoc}
     */
    protected function createRule(): AbstractRule
    {
        return new RepeatFrequency();
    }

    /**
     * {@inheritdoc}
     */
    protected function createValidInput(): array
    {
        return Collection::make(FrequencyType::cases())
          ->mapWithKeys(fn (FrequencyType $type): array => [
            $type->name => ['type' => $type->name, 'interval' => \mt_rand(1, 255)]
          ])
          ->toArray();
    }

    /**
     * {@inheritdoc}
     */
    protected function createInvalidInput(): array
    {
        return [
          'invalid type' => \mt_rand(1, 255),
          'empty' => '',
          'null' => null,
          'contains invalid type' => ['type' => 'invalid', 'interval' => \mt_rand(1, 255)],
          'contains invalid interval' => ['type' => FrequencyType::DAILY->name, 'interval' => 0]
        ];
    }
}
