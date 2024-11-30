<?php

namespace App\Http\Controllers\API;

use App\Domains\Feedback\Entities\Feedback;
use App\Http\Controllers\Controller;
use App\Http\Encoders\Feedback\FeedbackEncoder;
use App\Http\Requests\API\Feedback\FindRequest;
use App\Http\Requests\API\Feedback\ListRequest;
use App\Http\Requests\API\Feedback\PersistRequest;
use App\UseCases\Feedback as UseCase;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * フィードバックAPI.
 */
class FeedbackController extends Controller
{
    /**
     * フィードバック追加.
     *
     * @param PersistRequest $request
     * @param UseCase $useCase
     */
    public function create(PersistRequest $request, UseCase $useCase)
    {
        $parameters = $request->validated();

        try {
            $useCase->persist(
                identifier: $parameters['identifier'],
                status: $parameters['status'],
                type: $parameters['type'],
                content: $parameters['content'],
                createdAt: $parameters['createdAt'],
                updatedAt: $parameters['updatedAt']
            );

            return new Response('', Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }
    }

    /**
     * フィードバック更新.
     *
     * @param PersistRequest $request
     * @param UseCase $useCase
     */
    public function update(PersistRequest $request, UseCase $useCase)
    {
        $parameters = $request->validated();

        try {
            $useCase->persist(
                identifier: $parameters['identifier'],
                status: $parameters['status'],
                type: $parameters['type'],
                content: $parameters['content'],
                createdAt: $parameters['createdAt'],
                updatedAt: $parameters['updatedAt']
            );

            return new Response('', Response::HTTP_NO_CONTENT);
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        } catch (\OutOfBoundsException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }

    /**
     * フィードバック取得.
     *
     * @param FindRequest $request
     * @param UseCase $useCase
     * @param FeedbackEncoder $encoder
     */
    public function find(FindRequest $request, UseCase $useCase, FeedbackEncoder $encoder)
    {
        $parameters = $request->validated();

        try {
            $feedback = $useCase->find($parameters['identifier']);

            return $encoder->encode($feedback);
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        } catch (\OutOfBoundsException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }

    /**
     * フィードバック一覧取得.
     *
     * @param ListRequest $request
     * @param UseCase $useCase
     * @param FeedbackEncoder $encoder
     */
    public function list(ListRequest $request, UseCase $useCase, FeedbackEncoder $encoder)
    {
        $request->validated();

        try {
            $feedbacks = $useCase->list($request->all());

            return [
              'feedback' => $feedbacks->map(
                  fn (Feedback $feedback): array => $encoder->encode($feedback)
              )->all()
            ];
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }
    }
}
