<?php

namespace App\Http\Controllers\API;

use App\Domains\Customer\Entities\Customer;
use App\Exceptions\ConflictException;
use App\Http\Controllers\Controller;
use App\Http\Encoders\Customer\CustomerEncoder;
use App\Http\Requests\API\Customer\AddRequest;
use App\Http\Requests\API\Customer\DeleteRequest;
use App\Http\Requests\API\Customer\FindRequest;
use App\Http\Requests\API\Customer\ListRequest;
use App\Http\Requests\API\Customer\UpdateRequest;
use App\UseCases\Customer as UseCase;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * 顧客API.
 */
class CustomerController extends Controller
{
    /**
     * 顧客追加.
     *
     * @param AddRequest $request
     * @param UseCase $useCase
     * @return Response
     */
    public function add(AddRequest $request, UseCase $useCase)
    {
        $parameters = $request->validated();

        try {
            $useCase->add(
                identifier: $parameters['identifier'],
                name: $parameters['name'],
                address: $parameters['address'],
                phone: $parameters['phone'],
                cemeteries: $parameters['cemeteries'],
                transactionHistories: $parameters['transactionHistories'],
            );

            return new Response('', Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestException($exception->getMessage());
        } catch (ConflictException $exception) {
            throw new ConflictHttpException($exception->getMessage());
        }
    }

    /**
     * 顧客更新.
     *
     * @param UpdateRequest $request
     * @param UseCase $useCase
     * @return Response
     */
    public function update(UpdateRequest $request, UseCase $useCase)
    {
        $parameters = $request->validated();

        try {
            $useCase->update(
                identifier: $parameters['identifier'],
                name: $parameters['name'],
                address: $parameters['address'],
                phone: $parameters['phone'],
                cemeteries: $parameters['cemeteries'],
                transactionHistories: $parameters['transactionHistories'],
            );

            return new Response('', Response::HTTP_NO_CONTENT);
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestException($exception->getMessage());
        } catch (\OutOfBoundsException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }

    /**
     * 顧客取得.
     */
    public function find(FindRequest $request, UseCase $useCase, CustomerEncoder $encoder)
    {
        $parameters = $request->validated();

        try {
            $customer = $useCase->find($parameters['identifier']);

            return  $encoder->encode($customer);
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestException($exception->getMessage());
        } catch (\OutOfBoundsException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }

    /**
     * 顧客一覧取得.
     */
    public function list(ListRequest $request, UseCase $useCase, CustomerEncoder $encoder)
    {
        $request->validated();

        try {
            $customers = $useCase->list($request->all());

            return [
                'customers' => $customers
                    ->map(fn (Customer $customer): array => $encoder->encode($customer))
                    ->all()
            ];
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestException($exception->getMessage());
        }
    }

    /**
     * 顧客削除.
     */
    public function delete(DeleteRequest $request, UseCase $useCase)
    {
        $parameters = $request->validated();

        try {
            $useCase->delete($parameters['identifier']);

            return new Response('', Response::HTTP_NO_CONTENT);
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestException($exception->getMessage());
        } catch (\OutOfBoundsException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }
}
