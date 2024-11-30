<?php

namespace App\Http\Controllers\API;

use App\Domains\User\Entities\User;
use App\Http\Controllers\Controller;
use App\Http\Encoders\User\UserEncoder;
use App\Http\Requests\API\User\AddRequest;
use App\Http\Requests\API\User\DeleteRequest;
use App\Http\Requests\API\User\FindRequest;
use App\Http\Requests\API\User\UpdateRequest;
use App\UseCases\User as UseCase;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * ユーザーAPI.
 */
class UserController extends Controller
{
    /**
     * ユーザー追加.
     *
     * @param AddRequest $request
     * @param UseCase $useCase
     */
    public function add(AddRequest $request, UseCase $useCase)
    {
        $parameters = $request->validated();

        try {
            $useCase->persist(
                identifier: $parameters['identifier'],
                name: $parameters['name'],
                address: $parameters['address'],
                phone: $parameters['phone'],
                email: $parameters['email'],
                password: $parameters['password'],
                role: $parameters['role']
            );

            return new Response('', Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }
    }

    /**
     * ユーザー更新.
     *
     * @param UpdateRequest $request
     * @param UseCase $useCase
     */
    public function update(UpdateRequest $request, UseCase $useCase)
    {
        $parameters = $request->validated();

        try {
            $useCase->persist(
                identifier: $parameters['identifier'],
                name: $parameters['name'],
                address: $parameters['address'],
                phone: $parameters['phone'],
                email: $parameters['email'],
                password: $parameters['password'],
                role: $parameters['role']
            );

            return new Response('', Response::HTTP_NO_CONTENT);
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        } catch (\OutOfBoundsException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }

    /**
     * ユーザー取得.
     *
     * @param FindRequest $request
     * @param UseCase $useCase
     * @param UserEncoder $encoder
     */
    public function find(
        FindRequest $request,
        UseCase $useCase,
        UserEncoder $encoder
    ) {
        $parameters = $request->validated();

        try {
            $user = $useCase->find($parameters['identifier']);

            return $encoder->encode($user);
        } catch (\OutOfBoundsException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }

    /**
     * ユーザー一覧取得.
     *
     * @param UseCase $useCase
     * @param UserEncoder $encoder
     */
    public function list(UseCase $useCase, UserEncoder $encoder)
    {
        $users = $useCase->list();

        return [
          'users' => $users
            ->map(fn (User $user): array => $encoder->encode($user))
            ->all()
        ];
    }

    /**
     * ユーザー削除.
     *
     * @param DeleteRequest $request
     * @param UseCase $useCase
     */
    public function delete(DeleteRequest $request, UseCase $useCase)
    {
        $parameters = $request->validated();

        try {
            $useCase->delete($parameters['identifier']);

            return new Response('', Response::HTTP_NO_CONTENT);
        } catch (\OutOfBoundsException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }
}
