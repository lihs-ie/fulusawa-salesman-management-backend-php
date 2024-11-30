<?php

namespace Tests\Support\Helpers\Http;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Validation\Validator;
use Tests\Mock\Translation\NullTranslator;

/**
 * リクエストクラスをインスタンス化する機能.
 */
trait RequestGeneratable
{
    /**
     * JSON形式のリクエストインスタンスを生成するヘルパ.
     */
    protected function createJsonRequest(
        string $class,
        $payload,
        array $routeParameters = [],
        array $query = [],
        bool $validate = true
    ): Request {
        if (!\is_subclass_of($class, Request::class) && $class !== Request::class) {
            throw new \InvalidArgumentException(\sprintf('%s is not a valid request class.', $class));
        }

        $request = $this->withRoute(new $class(
            $query,
            [], // request
            [], // attribute
            [], // cookies
            [], // files
            [
                'REQUEST_METHOD' => 'POST',
                'CONTENT_TYPE' => 'application/json',
            ],
            \json_encode($payload)
        ), $routeParameters);

        if ($request instanceof FormRequest && $validate) {
            return $this->validateRequest($request);
        }

        return $request;
    }

    /**
     * Getリクエストインスタンスを生成するヘルパ.
     */
    protected function createGetRequest(
        string $class,
        array $query = [],
        array $routeParameters = [],
        bool $validate = true
    ): Request {
        if (!\is_subclass_of($class, Request::class) && $class !== Request::class) {
            throw new \InvalidArgumentException(\sprintf('%s is not a valid request class.', $class));
        }

        $request = $this->withRoute(new $class($query), $routeParameters);

        if ($request instanceof FormRequest && $validate) {
            return $this->validateRequest($request);
        }

        return $request;
    }

    /**
     * 指定したルートパラメータを持つRouteインスタンスをリクエストに結びつけるヘルパ.
     */
    protected function withRoute(Request $request, array $parameters): Request
    {
        $route = $this->createMock(Route::class);
        $route
            ->method('parameter')
            ->will($this->returnCallback(function ($name, $default = null) use ($parameters) {
                return $parameters[$name] ?? $default;
            }))
        ;

        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        return $request;
    }

    /**
     * フォームリクエストインスタンスのバリデーションを実行する.
     */
    protected function validateRequest(FormRequest $request, ?array $rules = null): FormRequest
    {
        $validator = new Validator(
            new NullTranslator(),
            $request->validationData(),
            $rules ?? $request->rules()
        );

        $request->setValidator($validator);

        return $request;
    }
}
