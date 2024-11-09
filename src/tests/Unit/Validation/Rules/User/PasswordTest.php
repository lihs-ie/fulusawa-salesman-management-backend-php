<?php

namespace Tests\Unit\Validation\Rules\User;

use App\Validation\Rules\AbstractRule;
use App\Validation\Rules\User\Password;
use Tests\TestCase;
use Tests\Unit\Validation\Rules\RuleTest;

/**
 * @group unit
 * @group validation
 * @group rules
 * @group user
 *
 * @coversNothing
 */
class PasswordTest extends TestCase
{
    use RuleTest;

    /**
     * {@inheritdoc}
     */
    protected function createRule(): AbstractRule
    {
        return new Password();
    }

    /**
     * {@inheritdoc}
     */
    protected function createValidInput(): array
    {
        return [
          'valid1' => 'Password1!',
          'valid2' => 'P@ssw0rd',
          'valid3' => 'P@ssw0rdExtraChars'
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function createInvalidInput(): array
    {
        return [
          'not contain lowercase' => 'PASSWORD1!',
          'not contain uppercase' => 'password1!',
          'not contain number' => 'Password!',
          'not contain symbol' => 'Password1',
          'not contain all requirements' => 'pass1!',
          'less than 8 characters' => 'Pass1!',
          'too long' => 'P@ssw0rdExtraCharsExtraChars' . str_repeat('ExtraChars', 100)
        ];
    }
}
