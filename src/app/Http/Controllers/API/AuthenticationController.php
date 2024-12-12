<?php

namespace App\Http\Controllers\API;

use App\Exceptions\InvalidTokenException;
use App\Http\Controllers\Controller;
use App\Http\Encoders\Authentication\AuthenticationEncoder;
use App\Http\Requests\API\Authentication\LoginRequest;
use App\Http\Requests\API\Authentication\LogoutRequest;
use App\Http\Requests\API\Authentication\RefreshRequest;
use App\Http\Requests\API\Authentication\TokenRequest;
use App\UseCases\Authentication as UseCase;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * 認証API.
 */
class AuthenticationController extends Controller
{
    /**
     * ログイン.
     */
    public function login(
        LoginRequest $request,
        UseCase $useCase,
        AuthenticationEncoder $encoder
    ) {
        $parameters = $request->validated();

        try {
            $identifier = $parameters['identifier'];
            $email = $parameters['email'];
            $password = $parameters['password'];

            $authentication = $useCase->persist($identifier, $email, $password);

            return $encoder->encode($authentication);
        } catch (\OutOfBoundsException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        } catch (UniqueConstraintViolationException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }
    }

    /**
     * ログアウト.
     */
    public function logout(
        LogoutRequest $request,
        UseCase $useCase
    ) {
        $parameters = $request->validated();

        try {
            $identifier = $parameters['identifier'];

            $useCase->logout($identifier);

            return new Response('', Response::HTTP_NO_CONTENT);
        } catch (\OutOfBoundsException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }

    /**
     * トークンの有効性を検証.
     */
    public function introspect(
        TokenRequest $request,
        UseCase $useCase
    ) {
        $parameters = $request->validated();

        try {
            $result = $useCase->introspection(
                value: $parameters['value'],
                type: $parameters['type']
            );

            return ['active' => $result];
        } catch (InvalidTokenException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }
    }

    /**
     * トークンの更新.
     */
    public function refresh(
        RefreshRequest $request,
        UseCase $useCase,
        AuthenticationEncoder $encoder
    ) {
        $parameters = $request->validated();

        try {
            $authentication = $useCase->refresh(
                value: $parameters['value'],
                type: $parameters['type']
            );

            return $encoder->encode($authentication);
        } catch (InvalidTokenException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }
    }

    /**
     * トークンの破棄.
     */
    public function revoke(
        TokenRequest $request,
        UseCase $useCase
    ) {
        $parameters = $request->validated();

        try {
            $useCase->revoke(
                value: $parameters['value'],
                type: $parameters['type']
            );

            return new Response('', Response::HTTP_NO_CONTENT);
        } catch (InvalidTokenException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }
    }
}
