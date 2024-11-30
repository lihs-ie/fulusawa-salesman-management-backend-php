<?php

namespace App\Http\Controllers\API;

use App\Domains\Cemetery\Entities\Cemetery;
use App\Exceptions\ConflictException;
use App\Http\Controllers\Controller;
use App\Http\Encoders\Cemetery\CemeteryEncoder;
use App\Http\Requests\API\Cemetery\AddRequest;
use App\Http\Requests\API\Cemetery\DeleteRequest;
use App\Http\Requests\API\Cemetery\FindRequest;
use App\Http\Requests\API\Cemetery\ListRequest;
use App\Http\Requests\API\Cemetery\UpdateRequest;
use App\UseCases\Cemetery as UseCase;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * 墓地情報API.
 */
class CemeteryController extends Controller
{
    /**
     * 墓地情報追加.
     *
     * @param AddRequest $request
     * @param UseCase $useCase
     */
    public function add(AddRequest $request, UseCase $useCase)
    {
        $parameter = $request->validated();

        try {
            $useCase->add(
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
        } catch (ConflictException $exception) {
            throw new ConflictHttpException($exception->getMessage());
        }
    }

    /**
     * 墓地情報更新.
     *
     * @param UpdateRequest $request
     * @param UseCase $useCase
     */
    public function update(UpdateRequest $request, UseCase $useCase)
    {
        $parameter = $request->validated();

        try {
            $useCase->update(
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
     * 墓地情報一覧取得.
     *
     * @param UseCase $useCase
     * @param CemeteryEncoder $encoder
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
     *
     * @param FindRequest $request
     * @param UseCase $useCase
     * @param CemeteryEncoder $encoder
     */
    public function find(FindRequest $request, UseCase $useCase, CemeteryEncoder $encoder)
    {
        $parameter = $request->validated();

        try {
            $cemetery = $useCase->find($parameter['identifier']);

            return $encoder->encode($cemetery);
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestException($exception->getMessage());
        } catch (\OutOfBoundsException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }

    /**
     * 墓地情報削除.
     *
     * @param DeleteRequest $request
     * @param UseCase $useCase
     */
    public function delete(DeleteRequest $request, UseCase $useCase)
    {
        $parameter = $request->validated();

        try {
            $useCase->delete($parameter['identifier']);

            return new Response('', Response::HTTP_NO_CONTENT);
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestException($exception->getMessage());
        } catch (\OutOfBoundsException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }
}
