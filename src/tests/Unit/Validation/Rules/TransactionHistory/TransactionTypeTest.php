<?php

namespace Tests\Unit\Validation\Rules\TransactionHistory;

use App\Validation\Rules\AbstractRule;
use App\Validation\Rules\TransactionHistory\TransactionType;
use Tests\TestCase;
use Tests\Unit\Validation\Rules\RuleTest;

/**
 * @group unit
 * @group validation
 * @group rules
 * @group transactionhistory
 *
 * @coversNothing
 */
class TransactionTypeTest extends TestCase
{
    use RuleTest;

    /**
     * {@inheritdoc}
     */
    protected function createRule(): AbstractRule
    {
        return new TransactionType();
    }

    /**
     * {@inheritdoc}
     */
    protected function createValidInput(): array
    {
        return [
          'maintenance' => 'MAINTENANCE',
          'cleaning' => 'CLEANING',
          'gravestone_installation' => 'GRAVESTONE_INSTALLATION',
          'gravestone_removal' => 'GRAVESTONE_REMOVAL',
          'gravestone_repair' => 'GRAVESTONE_REPAIR',
          'gravestone_replacement' => 'GRAVESTONE_REPAIR',
          'other' => 'OTHER'
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
