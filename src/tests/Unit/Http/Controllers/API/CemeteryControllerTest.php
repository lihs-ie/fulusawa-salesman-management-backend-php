<?php

namespace Tests\Unit\Http\Controllers\API;

use App\Domains\Cemetery\Entities\Cemetery as Entity;
use App\Domains\Cemetery\ValueObjects\CemeteryType;
use App\Exceptions\ConflictException;
use App\Http\Controllers\API\CemeteryController;
use App\Http\Encoders\Cemetery\CemeteryEncoder;
use App\Http\Requests\API\Cemetery\AddRequest;
use App\Http\Requests\API\Cemetery\DeleteRequest;
use App\Http\Requests\API\Cemetery\FindRequest;
use App\Http\Requests\API\Cemetery\ListRequest;
use App\Http\Requests\API\Cemetery\PersistRequest;
use App\Http\Requests\API\Cemetery\UpdateRequest;
use App\UseCases\Cemetery as UseCase;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Support\DependencyBuildable;
use Tests\Support\Helpers\Http\RequestGeneratable;
use Tests\TestCase;

/**
 * @group unit
 * @group http
 * @group controllers
 * @group api
 * @group cemetery
 *
 * @coversNothing
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class CemeteryControllerTest extends TestCase
{
    use DependencyBuildable;
    use RequestGeneratable;

    /**
     * テストに使用するインスタンス.
     */
    private ?Enumerable $instances;

    /**
     * テストに使用するエンコーダ.
     */
    private CemeteryEncoder $encoder;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->instances = $this->createInstances();
        $this->encoder = $this->builder()->create(CemeteryEncoder::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        $this->instances = null;

        parent::tearDown();
    }

    /**
     * @testdox testInstantiateSuccess インスタンスを生成できること.
     */
    public function testInstantiateSuccess(): void
    {
        $controller = new CemeteryController();

        $this->assertInstanceOf(CemeteryController::class, $controller);
    }

    /**
     * @testdox testAddReturnsResponse addメソッドで墓地情報を永続化できること.
     */
    public function testAddReturnsResponse(): void
    {
        $controller = new CemeteryController();

        $payload = [
            'identifier' => Uuid::uuid7()->toString(),
            'customer' => Uuid::uuid7()->toString(),
            'name' => Str::random(\mt_rand(1, 255)),
            'type' => Collection::make(CemeteryType::cases())->random()->name,
            'construction' => CarbonImmutable::now()->toAtomString(),
            'inHouse' => (bool) \mt_rand(0, 1)
        ];

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('add')
            ->with(...$payload);

        $request = $this->createJsonRequest(
            AddRequest::class,
            $payload
        );

        $actual = $controller->add($request, $useCase);

        $this->assertInstanceOf(Response::class, $actual);
        $this->assertSame(Response::HTTP_CREATED, $actual->getStatusCode());
    }

    /**
     * @testdox testAddThrowsBadRequestWithInvalidPayload createメソッドに不正な値が与えられたときBadRequestExceptionがスローされること.
     */
    public function testAddThrowsBadRequestWithInvalidPayload(): void
    {
        $controller = new CemeteryController();

        $payload = [
            'identifier' => Uuid::uuid7()->toString(),
            'customer' => Uuid::uuid7()->toString(),
            'name' => Str::random(\mt_rand(1, 255)),
            'type' => Collection::make(CemeteryType::cases())->random()->name,
            'construction' => CarbonImmutable::now()->toAtomString(),
            'inHouse' => (bool) \mt_rand(0, 1)
        ];

        $request = $this->createJsonRequest(
            AddRequest::class,
            $payload
        );

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('add')
            ->with(...$payload)
            ->willThrowException(new \InvalidArgumentException());

        $this->expectException(BadRequestException::class);

        $controller->add($request, $useCase);
    }

    /**
     * @testdox testAddThrowsBadRequestWithUnexpectedValue createメソッドに不正な値が与えられたときBadRequestExceptionがスローされること.
     */
    public function testAddThrowsBadRequestWithUnexpectedValue(): void
    {
        $controller = new CemeteryController();

        $payload = [
            'identifier' => Uuid::uuid7()->toString(),
            'customer' => Uuid::uuid7()->toString(),
            'name' => Str::random(\mt_rand(1, 255)),
            'type' => Collection::make(CemeteryType::cases())->random()->name,
            'construction' => CarbonImmutable::now()->toAtomString(),
            'inHouse' => (bool) \mt_rand(0, 1)
        ];

        $request = $this->createJsonRequest(
            AddRequest::class,
            $payload
        );

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('add')
            ->with(...$payload)
            ->willThrowException(new \UnexpectedValueException());

        $this->expectException(BadRequestException::class);

        $controller->add($request, $useCase);
    }

    /**
     * @testdox testAddThrowsConflictHttpExceptionWithConflictException createメソッドで識別子の競合が発生したときConflictHttpExceptionがスローされること.
     */
    public function testAddThrowsConflictHttpExceptionWithConflictException(): void
    {
        $controller = new CemeteryController();

        $payload = [
            'identifier' => Uuid::uuid7()->toString(),
            'customer' => Uuid::uuid7()->toString(),
            'name' => Str::random(\mt_rand(1, 255)),
            'type' => Collection::make(CemeteryType::cases())->random()->name,
            'construction' => CarbonImmutable::now()->toAtomString(),
            'inHouse' => (bool) \mt_rand(0, 1)
        ];

        $request = $this->createJsonRequest(
            AddRequest::class,
            $payload
        );

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('add')
            ->with(...$payload)
            ->willThrowException(new ConflictException());

        $this->expectException(ConflictHttpException::class);

        $controller->add($request, $useCase);
    }

    /**
     * @testdox testUpdateReturnsResponse updateメソッドで既存の墓地情報を更新できること.
     */
    public function testUpdateReturnsResponse(): void
    {
        $controller = new CemeteryController();

        $target = $this->instances->random();

        $payload = [
            'identifier' => $target->identifier()->value(),
            'customer' => Uuid::uuid7()->toString(),
            'name' => Str::random(\mt_rand(1, 255)),
            'type' => Collection::make(CemeteryType::cases())->random()->name,
            'construction' => CarbonImmutable::now()->toAtomString(),
            'inHouse' => (bool) \mt_rand(0, 1)
        ];

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('update')
            ->with(...$payload);

        $request = $this->createJsonRequest(
            UpdateRequest::class,
            $payload,
            ['identifier' => $target->identifier()->value()]
        );

        $actual = $controller->update($request, $useCase);

        $this->assertInstanceOf(Response::class, $actual);
        $this->assertSame(Response::HTTP_NO_CONTENT, $actual->getStatusCode());
    }

    /**
     * @testdox testUpdateThrowsBadRequestWhenInvalidPayload updateメソッドで不正なペイロードが指定された場合はBadRequestExceptionがスローされること.
     */
    public function testUpdateThrowsBadRequestWhenInvalidPayload(): void
    {
        $controller = new CemeteryController();

        $payload = [
            'identifier' => Uuid::uuid7()->toString(),
            'customer' => Uuid::uuid7()->toString(),
            'name' => Str::random(\mt_rand(1, 255)),
            'type' => Collection::make(CemeteryType::cases())->random()->name,
            'construction' => CarbonImmutable::now()->toAtomString(),
            'inHouse' => (bool) \mt_rand(0, 1)
        ];

        $request = $this->createJsonRequest(
            class: UpdateRequest::class,
            payload: $payload,
            routeParameters: ['identifier' => $payload['identifier']]
        );

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('update')
            ->with(...$payload)
            ->willThrowException(new \InvalidArgumentException());

        $this->expectException(BadRequestException::class);

        $controller->update($request, $useCase);
    }

    /**
     * @testdox testUpdateThrowsNotFoundWhenMissingIdentifier updateメソッドで存在しない識別子が指定された場合はNotFoundHttpExceptionがスローされること.
     */
    public function testUpdateThrowsNotFoundWhenMissingIdentifier(): void
    {
        $controller = new CemeteryController();

        $payload = [
            'identifier' => Uuid::uuid7()->toString(),
            'customer' => Uuid::uuid7()->toString(),
            'name' => Str::random(\mt_rand(1, 255)),
            'type' => Collection::make(CemeteryType::cases())->random()->name,
            'construction' => CarbonImmutable::now()->toAtomString(),
            'inHouse' => (bool) \mt_rand(0, 1)
        ];

        $request = $this->createJsonRequest(
            class: UpdateRequest::class,
            payload: $payload,
            routeParameters: ['identifier' => $payload['identifier']]
        );

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('update')
            ->with(...$payload)
            ->willThrowException(new \OutOfBoundsException());

        $this->expectException(NotFoundHttpException::class);

        $controller->update($request, $useCase);
    }

    /**
     * @testdox testListReturnsSuccessfulResponse listメソッドで墓地情報の一覧を取得できること.
     */
    public function testListReturnsSuccessfulResponse(): void
    {
        $controller = new CemeteryController();

        $expected = $this->instances->map(
            fn (Entity $entity): array => $this->encoder->encode($entity)
        )
            ->all();

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('list')
            ->with([])
            ->willReturn($this->instances);

        $request = $this->createGetRequest(
            class: ListRequest::class,
            query: [],
        );

        $actual = $controller->list($request, $useCase, $this->encoder);

        $this->assertSame($expected, $actual['cemeteries']);
    }

    /**
     * @testdox testFindReturnsResponse findメソッドで指定した識別子の墓地情報を取得できること.
     */
    public function testFindReturnsResponse(): void
    {
        $target = $this->instances->random();
        $expected = $this->encoder->encode($target);

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('find')
            ->with($target->identifier()->value())
            ->willReturn($target);

        $controller = new CemeteryController();

        $request = $this->createGetRequest(
            FindRequest::class,
            [],
            ['identifier' => $target->identifier()->value()]
        );

        $actual = $controller->find($request, $useCase, $this->encoder);

        $this->assertSame($expected, $actual);
    }

    /**
     * @testdox testFindThrowsBadRequestWhenInvalidIdentifier findメソッドで不正な識別子が指定された場合はBadRequestExceptionがスローされること.
     */
    public function testFindThrowsBadRequestWhenInvalidIdentifier(): void
    {
        $controller = new CemeteryController();

        $identifier = Uuid::uuid7()->toString();

        $request = $this->createGetRequest(
            class: FindRequest::class,
            query: [],
            routeParameters: ['identifier' => $identifier]
        );

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('find')
            ->with($identifier)
            ->willThrowException(new \InvalidArgumentException());

        $this->expectException(BadRequestException::class);

        $controller->find($request, $useCase, $this->encoder);
    }

    /**
     * @testdox testFindThrowsNotFoundWithMissingIdentifier findメソッドで存在しない識別子が指定された場合はNotFoundHttpExceptionがスローされること.
     */
    public function testFindThrowsNotFoundWithMissingIdentifier(): void
    {
        $controller = new CemeteryController();

        $missing = Uuid::uuid7()->toString();

        $request = $this->createGetRequest(
            FindRequest::class,
            [],
            ['identifier' => $missing]
        );

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('find')
            ->with($missing)
            ->willThrowException(new \OutOfBoundsException());

        $this->expectException(NotFoundHttpException::class);

        $controller->find($request, $useCase, $this->encoder);
    }

    /**
     * @testdox testDeleteReturnsSuccessfulResponse deleteメソッドで墓地情報を削除できること.
     */
    public function testDeleteReturnsSuccessfulResponse(): void
    {
        $controller = new CemeteryController();

        $target = $this->instances->random();

        $request = $this->createJsonRequest(
            class: DeleteRequest::class,
            payload: [],
            routeParameters: ['identifier' => $target->identifier()->value()]
        );

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('delete')
            ->with($target->identifier()->value());

        $actual = $controller->delete($request, $useCase);

        $this->assertInstanceOf(Response::class, $actual);
        $this->assertSame(Response::HTTP_NO_CONTENT, $actual->getStatusCode());
    }

    /**
     * @testdox testDeleteThrowsNotFoundWhenMissingIdentifier deleteメソッドで存在しない識別子が指定された場合はNotFoundHttpExceptionがスローされること.
     */
    public function testDeleteThrowsNotFoundWhenMissingIdentifier(): void
    {
        $controller = new CemeteryController();

        $missing = Uuid::uuid7()->toString();

        $request = $this->createJsonRequest(
            class: DeleteRequest::class,
            payload: [],
            routeParameters: ['identifier' => $missing]
        );

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('delete')
            ->with($missing)
            ->willThrowException(new \OutOfBoundsException());

        $this->expectException(NotFoundHttpException::class);

        $controller->delete($request, $useCase);
    }

    /**
     * @testdox testDeleteThrowsBadRequestWhenInvalidIdentifier deleteメソッドで不正な識別子が指定された場合はBadRequestExceptionがスローされること.
     */
    public function testDeleteThrowsBadRequestWhenInvalidIdentifier(): void
    {
        $controller = new CemeteryController();

        $identifier = Uuid::uuid7()->toString();

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('delete')
            ->with($identifier)
            ->willThrowException(new \InvalidArgumentException());

        $request = $this->createJsonRequest(
            class: DeleteRequest::class,
            payload: [],
            routeParameters: ['identifier' => $identifier]
        );

        $this->expectException(BadRequestException::class);

        $controller->delete($request, $useCase);
    }

    /**
     * テストに使用するインスタンスを生成する.
     */
    private function createInstances(): Enumerable
    {
        return $this->builder()->createList(Entity::class, \mt_rand(5, 10));
    }
}
