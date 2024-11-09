<?php

namespace App\Http\Controllers\API;

use App\Domains\Visit\Entities\Visit;
use App\Http\Controllers\Controller;
use App\Http\Encoders\Visit\VisitEncoder;
use App\Http\Requests\API\Visit\AddRequest;
use App\Http\Requests\API\Visit\DeleteRequest;
use App\Http\Requests\API\Visit\FindRequest;
use App\Http\Requests\API\Visit\UpdateRequest;
use App\UseCases\Visit as UseCase;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * 訪問API.
 */
class VisitController extends Controller
{
    /**
     * 訪問追加.
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
                user: $parameters['user'],
                visitedAt: $parameters['visitedAt'],
                address: $parameters['address'],
                phone: $parameters['phone'],
                hasGraveyard: $parameters['hasGraveyard'],
                note: $parameters['note'],
                result: $parameters['result']
            );

            return new Response('', Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }
    }

    /**
     * 訪問更新.
     *
     * @param UpdateRequest $request
     * @param UseCase $useCase
     */
    public function update(
        UpdateRequest $request,
        UseCase $useCase,
    ) {
        $parameters = $request->validated();

        try {
            $useCase->persist(
                identifier: $parameters['identifier'],
                user: $parameters['user'],
                visitedAt: $parameters['visitedAt'],
                address: $parameters['address'],
                phone: $parameters['phone'],
                hasGraveyard: $parameters['hasGraveyard'],
                note: $parameters['note'],
                result: $parameters['result']
            );

            return new Response('', Response::HTTP_NO_CONTENT);
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        } catch (\OutOfBoundsException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }

    /**
     * 訪問取得.
     *
     * @param FindRequest $request
     * @param UseCase $useCase
     * @param VisitEncoder $encoder
     */
    public function find(
        FindRequest $request,
        UseCase $useCase,
        VisitEncoder $encoder
    ) {
        $identifier = $request->route('identifier');

        try {
            $visit = $useCase->find($identifier);

            return $encoder->encode($visit);
        } catch (\OutOfBoundsException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }

    /**
     * 訪問一覧取得.
     */
    public function list(UseCase $useCase, VisitEncoder $encoder)
    {
        $visits = $useCase->list();

        return [
          'visits' => $visits
            ->map(fn (Visit $visit): array => $encoder->encode($visit))
            ->all()
        ];
    }

    /**
     * 訪問削除.
     *
     * @param DeleteRequest $request
     * @param UseCase $useCase
     */
    public function delete(DeleteRequest $request, UseCase $useCase)
    {
        $identifier = $request->route('identifier');

        try {
            $useCase->delete($identifier);

            return new Response('', Response::HTTP_NO_CONTENT);
        } catch (\OutOfBoundsException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }
}
