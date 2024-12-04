<?php

namespace App\Http\Controllers\API;

use App\Domains\Schedule\Entities\Schedule;
use App\Http\Controllers\Controller;
use App\Http\Encoders\Schedule\ScheduleEncoder;
use App\Http\Requests\API\Schedule\AddRequest;
use App\Http\Requests\API\Schedule\DeleteRequest;
use App\Http\Requests\API\Schedule\FindRequest;
use App\Http\Requests\API\Schedule\ListRequest;
use App\Http\Requests\API\Schedule\UpdateRequest;
use App\UseCases\Schedule as UseCase;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * スケジュールAPI.
 */
class ScheduleController extends Controller
{
    /**
     * スケジュール追加.
     *
     * @param AddRequest $request
     * @param UseCase $useCase
     */
    public function add(AddRequest $request, UseCase $useCase)
    {
        $parameters = $request->validated();

        try {
            $useCase->add(
                identifier: $parameters['identifier'],
                participants: $parameters['participants'],
                creator: $parameters['creator'],
                updater: $parameters['updater'],
                customer: $parameters['customer'],
                content: $parameters['content'],
                date: $parameters['date'],
                status: $parameters['status'],
                repeatFrequency: $parameters['repeatFrequency'],
            );

            return new Response('', Response::HTTP_CREATED);
        } catch (\InvalidArgumentException | \UnexpectedValueException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }
    }

    /**
     * スケジュール更新.
     *
     * @param UpdateRequest $request
     * @param UseCase $useCase
     */
    public function update(UpdateRequest $request, UseCase $useCase)
    {
        $parameters = $request->validated();

        try {
            $useCase->update(
                identifier: $parameters['identifier'],
                participants: $parameters['participants'],
                creator: $parameters['creator'],
                updater: $parameters['updater'],
                customer: $parameters['customer'],
                content: $parameters['content'],
                date: $parameters['date'],
                status: $parameters['status'],
                repeatFrequency: $parameters['repeatFrequency'],
            );

            return  new Response('', Response::HTTP_NO_CONTENT);
        } catch (\InvalidArgumentException | \UnexpectedValueException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        } catch (\OutOfBoundsException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }

    /**
     * スケジュール取得.
     *
     * @param FindRequest $request
     * @param UseCase $useCase
     */
    public function find(FindRequest $request, UseCase $useCase, ScheduleEncoder $encoder)
    {
        $parameters = $request->validated();

        try {
            $schedule = $useCase->find($parameters['identifier']);

            return $encoder->encode($schedule);
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        } catch (\OutOfBoundsException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }

    /**
     * スケジュール一覧取得.
     *
     * @param ListRequest $request
     * @param UseCase $useCase
     */
    public function list(ListRequest $request, UseCase $useCase, ScheduleEncoder $encoder)
    {
        $parameters = $request->validated();

        try {
            $schedules = $useCase->list($parameters);

            return [
                'schedules' => $schedules->map(
                    fn (Schedule $schedule): array => $encoder->encode($schedule)
                )->all(),
            ];
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }
    }

    /**
     * スケジュール削除.
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
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        } catch (\OutOfBoundsException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }
}
