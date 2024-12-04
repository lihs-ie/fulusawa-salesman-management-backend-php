<?php

namespace Tests\Unit\Validation\Rules\Schedule;

use App\Domains\Schedule\ValueObjects\ScheduleContent as ValueObjectsScheduleContent;
use App\Validation\Rules\AbstractRule;
use App\Validation\Rules\Schedule\ScheduleContent;
use Illuminate\Support\Str;
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
class ScheduleContentTest extends TestCase
{
    use RuleTest;

    /**
     * {@inheritdoc}
     */
    protected function createRule(): AbstractRule
    {
        return new ScheduleContent();
    }

    /**
     * {@inheritdoc}
     */
    protected function createValidInput(): array
    {
        return [
          'fulfilled' => [
            'title' => Str::random(\mt_rand(1, ValueObjectsScheduleContent::MAX_TITLE_LENGTH)),
            'description' => Str::random(\mt_rand(1, ValueObjectsScheduleContent::MAX_DESCRIPTION_LENGTH))
          ],
          'description is null' => [
            'title' => Str::random(\mt_rand(1, ValueObjectsScheduleContent::MAX_TITLE_LENGTH)),
            'description' => null
          ],
        ];
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
          'too long title' => [
            'title' => Str::random(ValueObjectsScheduleContent::MAX_TITLE_LENGTH + 1),
            'description' => Str::random(\mt_rand(1, ValueObjectsScheduleContent::MAX_DESCRIPTION_LENGTH))
          ],
          'empty title' => [
            'title' => '',
            'description' => Str::random(\mt_rand(1, ValueObjectsScheduleContent::MAX_DESCRIPTION_LENGTH))
          ],
          'too long description' => [
            'title' => Str::random(\mt_rand(1, ValueObjectsScheduleContent::MAX_TITLE_LENGTH)),
            'description' => Str::random(ValueObjectsScheduleContent::MAX_DESCRIPTION_LENGTH + 1)
          ],
          'empty description' => [
            'title' => Str::random(\mt_rand(1, ValueObjectsScheduleContent::MAX_TITLE_LENGTH)),
            'description' => ''
          ],
        ];
    }
}
