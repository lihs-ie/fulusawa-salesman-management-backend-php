<?php

namespace Tests\Unit\Http\Requests\API;

use Illuminate\Support\Arr;
use Tests\Support\Helpers\Http\RequestGeneratable;

/**
 * Query（参照系）APIリクエストの基底テスト.
 */
trait QueryRequestTest
{
    use AbstractRequestTest;
    use RequestGeneratable;

    /**
     * テスト対象のリクエストクラスのFQCNを取得する.
     */
    abstract protected function target(): string;

    /**
     * 基本となるクエリパラメータを生成する.
     */
    abstract protected function createDefaultQuery(): array;

    /**
     * 基本となるルートパラメータ値を生成する.
     */
    abstract protected function createDefaultRoute(): array;

    /**
     * 正常なパラメータのパターン定義を生成する.
     *
     * 返り値は [任意の名称 => 変換器] の構造を持つ連想配列
     * 変換器については mutateInput を参照のこと
     */
    abstract protected function getValidQueryPatterns(): array;

    /**
     * 正常なルートパラメータ値のパターン定義を生成する.
     *
     * 返り値は getValidPayloadPatterns と同様
     */
    abstract protected function getValidRoutePatterns(): array;

    /**
     * 異常なパラメータのパターン定義を生成する.
     *
     * 返り値は [フィールドパス => [任意の名称 => [変換器, エラーフィールドパスのリスト]]] の構造を持つ配列
     * フィールドパスは対象フィールドのdottedパスとする
     * 変換器については mutateInput を参照のこと
     */
    abstract protected function getInvalidQueryPatterns(): array;

    /**
     * 異常なルートパラメータ値のパターン定義を生成する.
     *
     * 返り値は getInvalidPayloadPatterns と同様
     */
    abstract protected function getInvalidRoutePatterns(): array;

    /**
     * {@inheritdoc}
     */
    protected function createValidRequests(): iterable
    {
        $class = $this->target();

        $query = $this->createDefaultQuery();
        $route = $this->createDefaultRoute();

        foreach ([
            'query' => $this->getValidQueryPatterns(),
            'route' => $this->getValidRoutePatterns(),
        ] as $target => $patterns) {
            foreach ($patterns as $name => $mutator) {
                $request = $this->createGetRequest(
                    $class,
                    ($target === 'query') ? $this->mutateInput($query, $mutator) : $query,
                    ($target === 'route') ? $this->mutateInput($route, $mutator) : $route,
                );
                $request->setMethod('GET');
                yield $name => $request;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createInvalidRequests(): iterable
    {
        $class = $this->target();

        $query = $this->createDefaultQuery();
        $route = $this->createDefaultRoute();

        foreach ([
            'query' => $this->getInvalidQueryPatterns(),
            'route' => $this->getInvalidRoutePatterns(),
        ] as $target => $patterns) {
            foreach ($patterns as $field => $definitions) {
                foreach ($definitions as $case => $definition) {
                    [$mutator, $errors] = $this->parseInvalidPattern($field, $definition);

                    $name = \sprintf('%s is %s', $field, $case);

                    yield $name => [
                        $this->createGetRequest(
                            $class,
                            ($target === 'query') ? $this->mutateInput($query, $mutator) : $query,
                            ($target === 'route') ? $this->mutateInput($route, $mutator) : $route,
                        ),
                        $errors,
                    ];
                }
            }
        }
    }

    /**
     * 不正な入力値の定義パターンを解析する.
     */
    protected function parseInvalidPattern(string $field, $definition): array
    {
        if ($definition instanceof \Closure) {
            return [$definition, [$field]];
        }

        if (\is_array($definition) && \array_key_exists('mutator', $definition)) {
            return [
                $definition['mutator'],
                $definition['errors'] ?? [$field],
            ];
        }

        return [
            [$field => $definition],
            [$field],
        ];
    }

    /**
     * 入力値の変換器を実行する.
     *
     * Closure: 入力全体を変換器に与えて返り値を変換後の値とする
     * array: キーをフィールドパスと見なして値をセットしたものを変換後の値とする
     * それ以外: 変換前の値をそのまま変換後の値とする
     */
    protected function mutateInput(array $input, $mutator): array
    {
        if ($mutator instanceof \Closure) {
            return $mutator($input);
        }

        if (\is_array($mutator)) {
            $result = $input;

            foreach ($mutator as $key => $value) {
                Arr::set($result, $key, $value);
            }

            return $result;
        }

        return $input;
    }
}
