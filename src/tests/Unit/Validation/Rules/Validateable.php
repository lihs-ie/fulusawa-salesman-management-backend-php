<?php

namespace Tests\Unit\Validation\Rules;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Validation\Validator;

/**
 * バリデーションを行う機能.
 */
trait Validateable
{
    /**
     * 指定した値とルールでバリデータを生成するヘルパ.
     */
    protected function validate(array $data, array $rules): Validator
    {
        return new Validator($this->createTranslator(), $data, $rules);
    }

    /**
     * トランスレータのモックを生成するヘルパ.
     */
    protected function createTranslator(): Translator
    {
        return $this->createMock(Translator::class);
    }
}
