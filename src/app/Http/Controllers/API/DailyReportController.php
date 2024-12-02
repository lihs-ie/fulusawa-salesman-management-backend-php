<?php

namespace App\Http\Controllers\API;

use App\Domains\DailyReport\Entities\DailyReport;
use App\Http\Controllers\Controller;
use App\Http\Encoders\DailyReport\DailyReportEncoder;
use App\Http\Requests\API\DailyReport\DeleteRequest;
use App\Http\Requests\API\DailyReport\FindRequest;
use App\Http\Requests\API\DailyReport\ListRequest;
use App\Http\Requests\API\DailyReport\PersistRequest;
use App\UseCases\DailyReport as UseCase;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * 日報API.
 */
class DailyReportController extends Controller
{
    /**
     * 日報作成.
     *
     * @param PersistRequest $request
     * @param UseCase $useCase
     * @return Response
     */
    public function create(PersistRequest $request, UseCase $useCase): Response
    {
        $parameters = $request->validated();

        try {
            $useCase->persist(
                identifier: $parameters['identifier'],
                user: $parameters['user'],
                date: $parameters['date'],
                schedules: $parameters['schedules'],
                visits: $parameters['visits'],
                isSubmitted: $parameters['isSubmitted'],
            );

            return new Response('', Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }
    }

    /**
     * 日報更新.
     *
     * @param PersistRequest $request
     * @param UseCase $useCase
     * @return Response
     */
    public function update(PersistRequest $request, UseCase $useCase): Response
    {
        $parameters = $request->validated();

        try {
            $useCase->persist(
                identifier: $parameters['identifier'],
                user: $parameters['user'],
                date: $parameters['date'],
                schedules: $parameters['schedules'],
                visits: $parameters['visits'],
                isSubmitted: $parameters['isSubmitted'],
            );

            return new Response('', Response::HTTP_NO_CONTENT);
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        } catch (\OutOfBoundsException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }

    /**
     * 日報取得.
     *
     * @param FindRequest $request
     * @param UseCase $useCase
     */
    public function find(
        FindRequest $request,
        UseCase $useCase,
        DailyReportEncoder $encoder
    ) {
        $parameters = $request->validated();

        try {
            $entity = $useCase->find($parameters['identifier']);

            return ['dailyReport' => $encoder->encode($entity)];
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        } catch (\OutOfBoundsException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }

    /**
     * 日報一覧取得.
     *
     * @param ListRequest $request
     * @param UseCase $useCase
     */
    public function list(
        ListRequest $request,
        UseCase $useCase,
        DailyReportEncoder $encoder
    ) {
        $request->validated();

        try {
            $dailyReports = $useCase->list($request->all());

            return [
              'dailyReports' => $dailyReports->map(fn (DailyReport $dailyReport): array => $encoder->encode($dailyReport))
                ->values()
                ->all(),
            ];
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }
    }

    /**
     * 日報削除.
     *
     * @param DeleteRequest $request
     * @param UseCase $useCase
     * @return Response
     */
    public function delete(DeleteRequest $request, UseCase $useCase): Response
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
