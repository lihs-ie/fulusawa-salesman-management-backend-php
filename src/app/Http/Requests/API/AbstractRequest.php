<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

/**
 * APIリクエストの基底クラス.
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
abstract class AbstractRequest extends FormRequest
{
    /**
     * バリデーション対象の入力値を取得する.
     *
     * 標準の対象はquery string, request body, headerなどを全てマージしたものになっている。
     * 定義されたリクエスト仕様に反したリクエストが通過してしまうため、
     * request method及びcontent typeに応じた入力源と、ルートパラメータのみを使用するよう変更する。
     *
     * {@inheritdoc}
     */
    public function validationData(): array
    {
        $routeParameters = [];

        foreach ($this->routeParameterNames() as $name) {
            $routeParameters[$name] = $this->route($name);
        }

        return $this->normalizeInput($routeParameters + $this->getInputSource()->all());
    }

    /**
     * バリデーション済みの入力値を取得する.
     *
     * 以下の不具合及び不都合な仕様が存在するためoverrideする
     *  * 「入力がキーごと存在しない」場合にValidationExceptionがthrowされない
     *  * 連想配列のキーにドットが含まれる場合、ドットがアローに変換される
     *  * 入力値がランダムで生成される文字列(Str::random(10))と偶然一致した場合、データが除外される
     *
     * {@inheritdoc}
     */
    public function validated($key = null, $default = null): array
    {
        if ($this->validator->messages()->isNotEmpty()) {
            throw new ValidationException($this->validator);
        }

        return $this->validationData();
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    abstract public function rules(): array;

    /**
     * バリデーション対象のルートパラメータ名のリストを取得する.
     */
    protected function routeParameterNames(): array
    {
        return [];
    }

    /**
     * 入力内容を取得する.
     *
     * GET, HEADメソッドの場合もリクエストボディを返す挙動が不都合であるためoverrideする
     *
     * {@inheritdoc}
     */
    protected function getInputSource()
    {
        if (\in_array($this->getRealMethod(), ['GET', 'HEAD'])) {
            return $this->query;
        }

        if ($this->isJson()) {
            return $this->json();
        }

        return $this->request;
    }

    /**
     * 入力内容の正規化を行う.
     *
     * query stringが全てstringとして解釈されてしまうため、必要に応じてキャストするためのフック
     */
    protected function normalizeInput(array $input): array
    {
        // デフォルトでは何もしない
        return $input;
    }
}
