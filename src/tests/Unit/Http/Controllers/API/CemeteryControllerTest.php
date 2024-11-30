<?php

namespace Tests\Unit\Http\Controllers\API;

use App\Domains\Cemetery\Entities\Cemetery as Entity;
use App\Domains\Cemetery\ValueObjects\CemeteryType;
use App\Http\Controllers\API\CemeteryController;
use App\Http\Encoders\Cemetery\CemeteryEncoder;
use App\Http\Requests\API\Cemetery\DeleteRequest;
use App\Http\Requests\API\Cemetery\FindRequest;
use App\Http\Requests\API\Cemetery\ListRequest;
use App\Http\Requests\API\Cemetery\PersistRequest;
use App\UseCases\Cemetery as UseCase;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
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
     * @testdox testListReturnsSuccessfulResponse listメソッドで墓地情報の一覧を取得できること.
     */
    public function testListReturnsSuccessfulResponse(): void
    {
        $controller = new CemeteryController();

        $expected = $this->instances->map(
            fn (Entity $entity): array => $this->encoder->encode($entity)
        )
          ->all();

        $useCase = $this->createUseCase('list', $this->instances);

        $request = $this->createGetRequest(
            class: ListRequest::class,
            query: [],
        );

        $actual = $controller->list($request, $useCase, $this->encoder);

        $this->assertSame($expected, $actual['cemeteries']);
    }

    /**
     * @testdox testFindReturnsSuccessfulResponse findメソッドで指定した墓地情報を取得できること.
     */
    public function testFindReturnsSuccessfulResponse(): void
    {
        $target = $this->instances->random();
        $expected = $this->encoder->encode($target);

        $useCase = $this->createUseCase('find', $target);

        $controller = new CemeteryController();

        $request = $this->createGetRequest(
            FindRequest::class,
            [],
            ['identifier' => $target->identifier()->value()]
        );

        $actual = $controller->find(
            $request,
            $useCase,
            $this->encoder
        );

        $this->assertSame($expected, $actual['cemetery']);
    }

    /**
     * @testdox testFindThrowsBadRequestWhenInvalidIdentifier findメソッドで不正な識別子が指定された場合はBadRequestExceptionがスローされること.
     */
    public function testFindThrowsBadRequestWhenInvalidIdentifier(): void
    {
        $controller = new CemeteryController();

        $request = $this->createMock(FindRequest::class);
        $request
          ->expects($this->once())
          ->method('validated')
          ->willReturn(['identifier' => 'invalid']);

        $useCase = $this->createUseCase(
            'find',
            null,
            ['identifier' => 'invalid'],
            new \InvalidArgumentException()
        );

        $this->expectException(BadRequestException::class);

        $controller->find(
            $request,
            $useCase,
            $this->encoder
        );
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

        $useCase = $this->createUseCase(
            'find',
            null,
            ['identifier' => $missing],
            new \OutOfBoundsException()
        );

        $this->expectException(NotFoundHttpException::class);

        $controller->find(
            $request,
            $useCase,
            $this->encoder
        );
    }

    /**
     * @testdox testCreateReturnsSuccessfulResponse createメソッドで新規の墓地情報を作成できること.
     */
    public function testCreateReturnsSuccessfulResponse(): void
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

        $useCase = $this->createUseCase('persist');

        $request = $this->createJsonRequest(
            PersistRequest::class,
            $payload
        );

        $actual = $controller->create(
            $request,
            $useCase
        );

        $this->assertSame(201, $actual->getStatusCode());
        $this->assertSame('', $actual->getContent());
    }

    /**
     * @testdox testCreateThrowsBadRequestWhenInvalidPayload createメソッドで不正なペイロードが指定された場合はBadRequestExceptionがスローされること.
     */
    public function testCreateThrowsBadRequestWhenInvalidPayload(): void
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
            PersistRequest::class,
            $payload
        );

        $useCase = $this->createUseCase(
            'persist',
            null,
            [...$payload],
            new \InvalidArgumentException()
        );

        $this->expectException(BadRequestException::class);

        $controller->create(
            $request,
            $useCase
        );
    }

    /**
     * @testdox testUpdateReturnsSuccessfulResponse updateメソッドで既存の墓地情報を更新できること.
     */
    public function testUpdateReturnsSuccessfulResponse(): void
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

        $useCase = $this->createUseCase('persist');

        $request = $this->createJsonRequest(
            PersistRequest::class,
            $payload
        );

        $actual = $controller->update(
            $request,
            $useCase
        );

        $this->assertSame(204, $actual->getStatusCode());
        $this->assertSame('', $actual->getContent());
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
            PersistRequest::class,
            $payload
        );

        $useCase = $this->createUseCase(
            'persist',
            null,
            [...$payload],
            new \InvalidArgumentException()
        );

        $this->expectException(BadRequestException::class);

        $controller->update(
            $request,
            $useCase
        );
    }

    /**
     * @testdox testUpdateThrowsNotFoundWhenMissingIdentifier updateメソッドで存在しない識別子が指定された場合はNotFoundHttpExceptionがスローされること.
     */
    public function testUpdateThrowsNotFoundWhenMissingIdentifier(): void
    {
        $controller = new CemeteryController();

        $missing = Uuid::uuid7()->toString();

        $payload = [
          'identifier' => $missing,
          'customer' => Uuid::uuid7()->toString(),
          'name' => Str::random(\mt_rand(1, 255)),
          'type' => Collection::make(CemeteryType::cases())->random()->name,
          'construction' => CarbonImmutable::now()->toAtomString(),
          'inHouse' => (bool) \mt_rand(0, 1)
        ];

        $request = $this->createJsonRequest(
            PersistRequest::class,
            $payload
        );

        $useCase = $this->createUseCase(
            'persist',
            null,
            [...$payload],
            new \OutOfBoundsException()
        );

        $this->expectException(NotFoundHttpException::class);

        $controller->update(
            $request,
            $useCase
        );
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

        $useCase = $this->createUseCase(
            method: 'delete',
            arguments: [$target->identifier()->value()]
        );

        $actual = $controller->delete(
            $request,
            $useCase
        );

        $this->assertSame(200, $actual->getStatusCode());
        $this->assertSame('', $actual->getContent());
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

        $useCase = $this->createUseCase(
            method: 'delete',
            arguments: [$missing],
            exception: new \OutOfBoundsException()
        );

        $this->expectException(NotFoundHttpException::class);

        $controller->delete(
            $request,
            $useCase
        );
    }

    /**
     * @testdox testDeleteThrowsBadRequestWhenInvalidIdentifier deleteメソッドで不正な識別子が指定された場合はBadRequestExceptionがスローされること.
     */
    public function testDeleteThrowsBadRequestWhenInvalidIdentifier(): void
    {
        $controller = new CemeteryController();

        $request = $this->createMock(DeleteRequest::class);
        $request
          ->expects($this->once())
          ->method('validated')
          ->willReturn(['identifier' => 'invalid']);

        $useCase = $this->createUseCase(
            method: 'delete',
            arguments: ['invalid'],
            exception: new \InvalidArgumentException()
        );

        $this->expectException(BadRequestException::class);

        $controller->delete(
            $request,
            $useCase
        );
    }

    /**
     * テストに使用するインスタンスを生成する.
     */
    private function createInstances(): Enumerable
    {
        return $this->builder()->createList(Entity::class, \mt_rand(5, 10));
    }

    /**
     * テストに使用するユースケースを生成する.
     */
    private function createUseCase(
        string $method,
        mixed $returns = null,
        array $arguments = [],
        ?\Exception $exception = null
    ): UseCase {
        $useCase = $this->createMock(UseCase::class);

        if ($returns) {
            $useCase
              ->expects($this->once())
              ->method($method)
              ->with(...$arguments)
              ->willReturn($returns);
        }

        if ($exception) {
            $useCase
              ->expects($this->once())
              ->method($method)
              ->with(...$arguments)
              ->willThrowException($exception);
        }

        return $useCase;
    }
}
