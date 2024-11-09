<?php

namespace App\Http\Controllers\API;

use Illuminate\Contracts\Validation\Validator;

/**
 * バリデーションの例外をコントローラーの呼び出しまで送出しないようにする機能.
 */
trait LazyThrowable
{
    /**
     * {@inheritdoc}
     */
    protected function failedValidation(Validator $validator): void
    {
    }
}
