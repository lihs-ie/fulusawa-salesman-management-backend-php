<?php

namespace Tests\Unit\Validation\Rules;

use App\Validation\Rules\AbstractRule;
use Illuminate\Validation\ValidationException;

/**
 * カスタムバリデーションルールの基底テスト.
 */
trait RuleTest
{
    use Validateable;

    /**
     * @testdox testInstantiationSuccess 正しいインスタンスが生成できること
     */
    public function testInstantiationSuccess(): void
    {
        $rule = $this->createRule();

        $this->assertInstanceOf(AbstractRule::class, $rule);
    }

    /**
     * @testdox testValidateNormalTerminationWithValidValue 正しい入力値に対してvalidateメソッドが正常終了すること
     *
     * @doesNotPerformAssertions
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function testValidateNormalTerminationWithValidValue(): void
    {
        $rule = $this->createRule();

        foreach ($this->createValidInput() as $name => $value) {
            $rule->validate($name, $value, fn (string $message) => throw ValidationException::withMessages([$name => [$message]]));
        }
    }

    /**
     * @testdox testPassesReturnsFalseWithInvalidValue 不正な入力値に対してpassesメソッドがfalseを返すこと
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function testPassesReturnsFalseWithInvalidValue(): void
    {
        $rule = $this->createRule();

        foreach ($this->createInvalidInput() as $name => $value) {
            $this->expectException(ValidationException::class);

            $rule->validate(
                'testField',
                $value,
                fn (string $message) => throw ValidationException::withMessages(['testField' => [$message]])
            );
        }
    }

    /**
     * テスト対象のルールインスタンスを生成するヘルパ.
     */
    abstract protected function createRule(): AbstractRule;

    /**
     * 正しい入力値を生成するヘルパ.
     *
     * 返り値は連想配列とし、キーが任意の名称、値がテスト対象の入力値とすること
     */
    abstract protected function createValidInput(): array;

    /**
     * 不正な入力値を生成するヘルパ.
     *
     * 返り値は連想配列とし、キーが任意の名称、値がテスト対象の入力値とすること
     */
    abstract protected function createInvalidInput(): array;
}
