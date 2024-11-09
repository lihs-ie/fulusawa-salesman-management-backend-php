<?php

namespace App\Http\Controllers\API;

use App\Domains\TransactionHistory\Entities\TransactionHistory;
use App\Http\Controllers\Controller;
use App\Http\Encoders\TransactionHistory\TransactionHistoryEncoder;
use App\Http\Requests\API\TransactionHistory\AddRequest;
use App\Http\Requests\API\TransactionHistory\DeleteRequest;
use App\Http\Requests\API\TransactionHistory\FindRequest;
use App\Http\Requests\API\TransactionHistory\ListRequest;
use App\Http\Requests\API\TransactionHistory\UpdateRequest;
use App\UseCases\TransactionHistory as UseCase;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * 取引履歴API.
 */
class TransactionHistoryController extends Controller
{
    /**
     * 取引履歴追加.
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
                customer: $parameters['customer'],
                type: $parameters['type'],
                description: $parameters['description'],
                date: $parameters['date'],
            );

            return new Response('', Response::HTTP_CREATED);
        } catch (\InvalidArgumentException | \UnexpectedValueException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }
    }

    /**
     * 取引履歴更新.
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
                user: $parameters['user'],
                customer: $parameters['customer'],
                type: $parameters['type'],
                description: $parameters['description'],
                date: $parameters['date'],
            );

            return new Response('', Response::HTTP_OK);
        } catch (\InvalidArgumentException | \UnexpectedValueException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        } catch (\OutOfBoundsException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }

    /**
     * 取引履歴一覧取得.
     *
     * @param UseCase $useCase
     * @param TransactionHistoryEncoder $encoder
     */
    public function list(
        UseCase $useCase,
        TransactionHistoryEncoder $encoder
    ) {
        $histories = $useCase->list();

        return [
          'transactionHistories' => $histories->map(
              fn (TransactionHistory $history): array => $encoder->encode($history)
          )
            ->all()
        ];
    }

    /**
     * 取引履歴取得.
     *
     * @param ListRequest $request
     * @param UseCase $useCase
     * @param TransactionHistoryEncoder $encoder
     */
    public function find(
        FindRequest $request,
        UseCase $useCase,
        TransactionHistoryEncoder $encoder
    ) {
        $parameters = $request->validated();

        try {
            $history = $useCase->find($parameters['identifier']);

            return $encoder->encode($history);
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        } catch (\OutOfBoundsException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }

    /**
     * 取引履歴削除.
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
        } catch (\InvalidArgumentException | \UnexpectedValueException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        } catch (\OutOfBoundsException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }
}
