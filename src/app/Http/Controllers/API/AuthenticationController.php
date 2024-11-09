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
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * 認証API
 */
class AuthenticationController extends Controller
{
    /**
     * ログイン.
     *
     * @param LoginRequest $request
     * @param UseCase $useCase
     * @param AuthenticationEncoder $encoder
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
        } catch (AuthorizationException $exception) {
            throw new AccessDeniedHttpException($exception->getMessage());
        } catch (\Illuminate\Database\UniqueConstraintViolationException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }
    }

    /**
     * ログアウト.
     *
     * @param LogoutRequest $request
     * @param UseCase $useCase
     */
    public function logout(
        LogoutRequest $request,
        UseCase $useCase
    ) {
        $parameters = $request->validated();

        try {
            $identifier = $parameters['identifier'];

            $useCase->logout($identifier);

            return [];
        } catch (\OutOfBoundsException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }

    /**
     * トークンの有効性を検証.
     *
     * @param TokenRequest $request
     * @param UseCase $useCase
     */
    public function introspect(
        TokenRequest $request,
        UseCase $useCase
    ) {
        $parameters = $request->validated();

        try {
            $token = $parameters['token'];

            $result = $useCase->introspection($token);

            return [
                'active' => $result,
            ];
        } catch (InvalidTokenException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }
    }

    /**
     * トークンの更新.
     *
     * @param RefreshRequest $request
     * @param UseCase $useCase
     * @param AuthenticationEncoder $encoder
     */
    public function refresh(
        RefreshRequest $request,
        UseCase $useCase,
        AuthenticationEncoder $encoder
    ) {
        $parameters = $request->validated();

        try {
            $token = $parameters['token'];

            $authentication = $useCase->refresh($token);

            return $encoder->encode($authentication);
        } catch (InvalidTokenException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }
    }

    /**
     * トークンの破棄.
     *
     * @param TokenRequest $request
     * @param UseCase $useCase
     */
    public function revoke(
        TokenRequest $request,
        UseCase $useCase
    ) {
        $parameters = $request->validated();

        try {
            $token = $parameters['token'];

            $useCase->revoke($token);

            return [];
        } catch (InvalidTokenException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }
    }
}
