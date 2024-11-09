<?php

namespace Tests\Unit\Http\Requests\API;

use Illuminate\Support\Collection;
use Tests\Unit\Validation\Rules\Validateable;

/**
 * APIリクエストクラスの基底テスト.
 */
trait AbstractRequestTest
{
    use Validateable;

    /**
     * @testdox testValidationPassesWithValidInput 正しいリクエストによってバリデーションを通過すること
     */
    public function testValidationPassesWithValidInput(): void
    {
        $dependencies = $this->createDependenciesOfRules();

        foreach ($this->createValidRequests() as $name => $request) {
            $input = $request->validationData();
            $rules = $request->rules(...$dependencies);

            $validator = $this->validate($input, $rules);

            $this->assertTrue(
                $validator->passes(),
                \sprintf(
                    "validation passes for `%s`.\ninput is %s\nmessages are %s",
                    $name,
                    \var_export($input, true),
                    \var_export($validator->errors()->messages(), true)
                )
            );
        }
    }

    /**
     * @testdox testValidationFailsWithInvalidInput 不正なリクエストによってバリデーションが失敗すること
     */
    public function testValidationFailsWithInvalidInput(): void
    {
        $dependencies = $this->createDependenciesOfRules();

        foreach ($this->createInvalidRequests() as $name => [$request, $errorFields]) {
            $input = $request->validationData();
            $rules = $request->rules(...$dependencies);

            $validator = $this->validate($input, $rules);

            $this->assertFalse(
                $validator->passes(),
                \sprintf(
                    "validation fails for `%s`.\ninput is %s",
                    $name,
                    \var_export($input, true)
                )
            );

            $errors = Collection::make($validator->errors()->messages());
            foreach ($errorFields as $field) {
                $this->assertTrue(
                    $errors->has($field),
                    \sprintf(
                        "errors contains `%s` on %s.\ninput is %s\nerrors are %s",
                        $field,
                        $name,
                        \var_export($input, true),
                        \var_export($validator->errors()->messages(), true)
                    )
                );
            }
        }
    }

    /**
     * テスト対象のrulesメソッドの引数を生成するヘルパ.
     */
    protected function createDependenciesOfRules(): array
    {
        return [];
    }

    /**
     * バリデーションを通過する入力値を持つリクエストを生成するヘルパ.
     *
     * 返り値は連想配列で、 [任意のデータ名 => リクエスト] の形式とする
     */
    abstract protected function createValidRequests(): iterable;

    /**
     * バリデーションに失敗する入力値を持つリクエストを生成するヘルパ.
     *
     * 返り値は連想配列で、
     *  [任意のデータ名 => [リクエスト, エラーが発生することを期待するフィールドのリスト]]
     * の形式とする
     */
    abstract protected function createInvalidRequests(): iterable;
}
