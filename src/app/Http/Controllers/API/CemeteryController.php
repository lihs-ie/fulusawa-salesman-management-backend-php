<?php

namespace App\Http\Controllers\API;

use App\Domains\Cemetery\Entities\Cemetery;
use App\Http\Controllers\Controller;
use App\Http\Encoders\Cemetery\CemeteryEncoder;
use App\Http\Requests\API\Cemetery\DeleteRequest;
use App\Http\Requests\API\Cemetery\FindRequest;
use App\Http\Requests\API\Cemetery\ListRequest;
use App\Http\Requests\API\Cemetery\PersistRequest;
use App\UseCases\Cemetery as UseCase;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * 墓地情報API.
 */
class CemeteryController extends Controller
{
    /**
     * 墓地情報一覧取得.
     *
     * @param UseCase $useCase
     * @param CemeteryEncoder $encoder
     * @return array
     */
    public function list(ListRequest $request, UseCase $useCase, CemeteryEncoder $encoder)
    {
        $request->validated();

        try {
            $cemeteries = $useCase->list($request->all());

            return [
              'cemeteries' => $cemeteries->map(
                  fn (Cemetery $cemetery): array => $encoder->encode($cemetery)
              )->all()
            ];
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestException($exception->getMessage());
        }
    }

    /**
     * 墓地情報取得.
     */
    public function find(FindRequest $request, UseCase $useCase, CemeteryEncoder $encoder)
    {
        $parameter = $request->validated();

        try {
            $cemetery = $useCase->find($parameter['identifier']);

            return ['cemetery' => $encoder->encode($cemetery)];
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestException($exception->getMessage());
        } catch (\OutOfBoundsException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }

    /**
     * 墓地情報作成.
     */
    public function create(PersistRequest $request, UseCase $useCase)
    {
        $parameter = $request->validated();

        try {
            $useCase->persist(
                identifier: $parameter['identifier'],
                customer: $parameter['customer'],
                name: $parameter['name'],
                type: $parameter['type'],
                construction: $parameter['construction'],
                inHouse: $parameter['inHouse'],
            );

            return new Response('', Response::HTTP_CREATED);
        } catch (\InvalidArgumentException | \UnexpectedValueException $exception) {
            throw new BadRequestException($exception->getMessage());
        } catch (\OutOfBoundsException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }

    /**
     * 墓地情報更新.
     */
    public function update(PersistRequest $request, UseCase $useCase)
    {
        $parameter = $request->validated();

        try {
            $useCase->persist(
                identifier: $parameter['identifier'],
                customer: $parameter['customer'],
                name: $parameter['name'],
                type: $parameter['type'],
                construction: $parameter['construction'],
                inHouse: $parameter['inHouse'],
            );

            return new Response('', Response::HTTP_NO_CONTENT);
        } catch (\InvalidArgumentException | \UnexpectedValueException $exception) {
            throw new BadRequestException($exception->getMessage());
        } catch (\OutOfBoundsException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }

    /**
     * 墓地情報削除.
     */
    public function delete(DeleteRequest $request, UseCase $useCase)
    {
        $parameter = $request->validated();

        try {
            $useCase->delete(
                identifier: $parameter['identifier']
            );

            return new Response('', Response::HTTP_OK);
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestException($exception->getMessage());
        } catch (\OutOfBoundsException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }
}
