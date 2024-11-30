<?php

namespace Tests\Unit\Validation\Rules\User;

use App\Validation\Rules\AbstractRule;
use App\Validation\Rules\User\Role;
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
class RoleTest extends TestCase
{
    use RuleTest;

    /**
     * {@inheritdoc}
     */
    protected function createRule(): AbstractRule
    {
        return new Role();
    }

    /**
     * {@inheritdoc}
     */
    protected function createValidInput(): array
    {
        return [
          'user' => 'USER',
          'admin' => 'ADMIN',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function createInvalidInput(): array
    {
        return [
          'invalid status' => \mt_rand(1, 255),
          'empty' => '',
          'null' => null
        ];
    }
}
