<?php

namespace App\Http\Controllers\API;

use App\Domains\Customer\Entities\Customer;
use App\Http\Controllers\Controller;
use App\Http\Encoders\Customer\CustomerEncoder;
use App\Http\Requests\API\Customer\DeleteRequest;
use App\Http\Requests\API\Customer\FindRequest;
use App\Http\Requests\API\Customer\PersistRequest;
use App\UseCases\Customer as UseCase;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * 顧客API.
 */
class CustomerController extends Controller
{
    /**
     * 顧客作成.
     *
     * @param PersistRequest $request
     * @param UseCase $useCase
     * @return Response
     */
    public function create(PersistRequest $request, UseCase $useCase)
    {
        $parameters = $request->validated();

        try {
            $useCase->persist(
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
        }
    }

    /**
     * 顧客更新.
     *
     * @param PersistRequest $request
     * @param UseCase $useCase
     * @return Response
     */
    public function update(PersistRequest $request, UseCase $useCase)
    {
        $parameters = $request->validated();

        try {
            $useCase->persist(
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

            return ['customer' => $encoder->encode($customer)];
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestException($exception->getMessage());
        } catch (\OutOfBoundsException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }

    /**
     * 顧客一覧取得.
     */
    public function list(UseCase $useCase, CustomerEncoder $encoder)
    {
        $customers = $useCase->list();

        return [
          'customers' => $customers->map(
              fn (Customer $customer): array => $encoder->encode($customer)
          )
            ->all()
        ];
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
