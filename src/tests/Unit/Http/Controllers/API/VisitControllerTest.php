<?php

namespace Tests\Unit\Http\Controllers\API;

use App\Domains\Common\ValueObjects\PhoneNumber;
use App\Domains\Visit\Entities\Visit as Entity;
use App\Http\Controllers\API\VisitController;
use App\Http\Encoders\Common\AddressEncoder;
use App\Http\Encoders\Common\PhoneNumberEncoder;
use App\Http\Encoders\Visit\VisitEncoder;
use App\Http\Requests\API\Visit\AddRequest;
use App\Http\Requests\API\Visit\DeleteRequest;
use App\Http\Requests\API\Visit\FindRequest;
use App\Http\Requests\API\Visit\UpdateRequest;
use App\UseCases\Visit as UseCase;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Support\Assertions\NullableValueComparable;
use Tests\Support\DependencyBuildable;
use Tests\Support\Helpers\Http\RequestGeneratable;
use Tests\TestCase;

/**
 * @group unit
 * @group http
 * @group controllers
 * @group api
 * @group visit
 *
 * @coversNothing
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class VisitControllerTest extends TestCase
{
    use DependencyBuildable;
    use NullableValueComparable;
    use RequestGeneratable;

    /**
     * テストに使用するインスタンス.
     */
    private Enumerable|null $instances;

    /**
     * テストに使用するエンコーダ.
     */
    private VisitEncoder $encoder;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->encoder = $this->builder()->create(VisitEncoder::class);
        $this->instances = $this->createInstances();
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown(): void
    {
        $this->instances = null;

        parent::tearDown();
    }

    /**
     * @testdox testInstantiateSuccess 正常な値によってインスタンスを生成できること.
     */
    public function testInstantiateSuccess(): void
    {
        $controller = new VisitController();

        $this->assertInstanceOf(VisitController::class, $controller);
    }

    /**
     * @testdox testAddSuccessReturnsResponse addメソッドに正常な値を与えたとき正常なレスポンスが返ること.
     */
    public function testAddSuccessReturnsResponse(): void
    {
        $instance = $this->builder()->create(Entity::class);

        $payload = $this->createPayload($instance);

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('persist')
          ->with(...$payload);

        $controller = new VisitController();

        $request = $this->createJsonRequest(
            class: AddRequest::class,
            payload: $payload
        );

        $actual = $controller->add($request, $useCase);

        $this->assertInstanceOf(Response::class, $actual);
        $this->assertSame(Response::HTTP_CREATED, $actual->getStatusCode());
    }

    /**
     * @testdox testAddThrowsBadRequestWithInvalidArgumentException addメソッドに不正な値を与えたときBadRequestHttpExceptionがスローされること.
     */
    public function testAddThrowsBadRequestWithInvalidArgumentException(): void
    {
        $payload = $this->createPayload($this->instances->first());

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('persist')
          ->with(...$payload)
          ->willThrowException(new \InvalidArgumentException());

        $controller = new VisitController();

        $request = $this->createJsonRequest(
            class: AddRequest::class,
            payload: $payload
        );

        $this->expectException(BadRequestHttpException::class);

        $controller->add($request, $useCase);
    }

    /**
     * @testdox testUpdateSuccessReturnsResponse updateメソッドに正常な値を与えたとき正常なレスポンスが返ること.
     */
    public function testUpdateSuccessReturnsResponse(): void
    {
        $instance = $this->instances->random();

        $next = $this->builder()->create(Entity::class, null, [
          'identifier' => $instance->identifier(),
        ]);

        $payload = $this->createPayload($next);

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('persist')
          ->with(...$payload);

        $controller = new VisitController();

        $request = $this->createJsonRequest(
            class: UpdateRequest::class,
            payload: $payload,
            routeParameters: ['identifier' => $instance->identifier()->value()]
        );

        $actual = $controller->update($request, $useCase);

        $this->assertInstanceOf(Response::class, $actual);
        $this->assertSame(Response::HTTP_NO_CONTENT, $actual->getStatusCode());
    }

    /**
     * @testdox testUpdateThrowsBadRequestWithInvalidArgumentException updateメソッドに不正な値を与えたときBadRequestHttpExceptionがスローされること.
     */
    public function testUpdateThrowsBadRequestWithInvalidArgumentException(): void
    {
        $instance = $this->instances->random();

        $payload = $this->createPayload($instance);

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('persist')
          ->with(...$payload)
          ->willThrowException(new \InvalidArgumentException());

        $controller = new VisitController();

        $request = $this->createJsonRequest(
            class: UpdateRequest::class,
            payload: $payload,
            routeParameters: ['identifier' => $instance->identifier()->value()]
        );

        $this->expectException(BadRequestHttpException::class);

        $controller->update($request, $useCase);
    }

    /**
     * @testdox testUpdateThrowsNotFoundWithMissingIdentifier updateメソッドに存在しない識別子を与えたときNotFoundHttpExceptionがスローされること.
     */
    public function testUpdateThrowsNotFoundWithMissingIdentifier(): void
    {
        $instance = $this->builder()->create(Entity::class);

        $payload = $this->createPayload($instance);

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('persist')
          ->with(...$payload)
          ->willThrowException(new \OutOfBoundsException());

        $controller = new VisitController();

        $request = $this->createJsonRequest(
            class: UpdateRequest::class,
            payload: $payload,
            routeParameters: ['identifier' => $instance->identifier()->value()]
        );

        $this->expectException(NotFoundHttpException::class);

        $controller->update($request, $useCase);
    }

    /**
     * @testdox testFindSuccessReturnsResponse findメソッドに正常な値を与えたとき正常なレスポンスが返ること.
     */
    public function testFindSuccessReturnsResponse(): void
    {
        $expected = $this->instances->random();

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('find')
          ->with($expected->identifier()->value())
          ->willReturn($expected);

        $controller = new VisitController();

        $request = $this->createGetRequest(
            class: FindRequest::class,
            routeParameters: ['identifier' => $expected->identifier()->value()]
        );

        $actual = $controller->find($request, $useCase, $this->encoder);

        $this->assertEntity($expected, $actual);
    }

    /**
     * @testdox testFindThrowsNotFoundWithMissingIdentifier findメソッドに存在しない識別子を与えたときNotFoundHttpExceptionがスローされること.
     */
    public function testFindThrowsNotFoundWithMissingIdentifier(): void
    {
        $identifier = $this->builder()->create(Entity::class)->identifier();

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('find')
          ->with($identifier->value())
          ->willThrowException(new \OutOfBoundsException());

        $controller = new VisitController();

        $request = $this->createGetRequest(
            class: FindRequest::class,
            routeParameters: ['identifier' => $identifier->value()]
        );

        $this->expectException(NotFoundHttpException::class);

        $controller->find($request, $useCase, $this->encoder);
    }

    /**
     * @testdox testListSuccessReturnsResponse listメソッドに正常な値を与えたとき正常なレスポンスが返ること.
     */
    public function testListSuccessReturnsResponse(): void
    {
        $expected = $this->instances;

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('list')
          ->willReturn($expected);

        $controller = new VisitController();

        $actual = $controller->list($useCase, $this->encoder);

        $expected
          ->zip(Collection::make($actual['visits']))
          ->eachSpread(function (?Entity $expected, ?array $actual): void {
              $this->assertNotNull($expected);
              $this->assertNotNull($actual);
              $this->assertEntity($expected, $actual);
          });
    }

    /**
     * @testdox testDeleteSuccessReturnsResponse deleteメソッドに正常な値を与えたとき正常なレスポンスが返ること.
     */
    public function testDeleteSuccessReturnsResponse(): void
    {
        $instance = $this->instances->random();

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('delete')
          ->with($instance->identifier()->value());

        $controller = new VisitController();

        $request = $this->createJsonRequest(
            class: DeleteRequest::class,
            payload: [],
            routeParameters: ['identifier' => $instance->identifier()->value()]
        );

        $actual = $controller->delete($request, $useCase);

        $this->assertInstanceOf(Response::class, $actual);
        $this->assertSame(Response::HTTP_NO_CONTENT, $actual->getStatusCode());
    }

    /**
     * @testdox testDeleteThrowsNotFoundWithMissingIdentifier deleteメソッドに存在しない識別子を与えたときNotFoundHttpExceptionがスローされること.
     */
    public function testDeleteThrowsNotFoundWithMissingIdentifier(): void
    {
        $identifier = $this->builder()->create(Entity::class)->identifier();

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('delete')
          ->with($identifier->value())
          ->willThrowException(new \OutOfBoundsException());

        $controller = new VisitController();

        $request = $this->createJsonRequest(
            class: DeleteRequest::class,
            payload: [],
            routeParameters: ['identifier' => $identifier->value()]
        );

        $this->expectException(NotFoundHttpException::class);

        $controller->delete($request, $useCase);
    }

    /**
     * テストに使用するインスタンスを生成するへルパ.
     */
    private function createInstances(): Enumerable
    {
        return $this->builder()->createList(
            class: Entity::class,
            count: \mt_rand(5, 10)
        );
    }

    /**
     * ペイロードを生成する.
     */
    private function createPayload(Entity $entity): array
    {
        return $this->encoder->encode($entity);
    }

    /**
     * エンティティと配列の内容を比較する.
     */
    private function assertEntity(Entity $expected, array $actual): void
    {
        $addressEncoder = $this->builder()->create(AddressEncoder::class);
        $phoneEncoder = $this->builder()->create(PhoneNumberEncoder::class);

        $this->assertIsArray($actual);
        $this->assertSame($expected->identifier()->value(), $actual['identifier']);
        $this->assertSame($expected->user()->value(), $actual['user']);
        $this->assertSame($expected->visitedAt()->toAtomString(), $actual['visitedAt']);
        $this->assertSame($addressEncoder->encode($expected->address()), $actual['address']);
        $this->assertNullOr(
            $expected->phone(),
            $actual['phone'],
            function (PhoneNumber $expected, array $actual) use ($phoneEncoder) {
                $this->assertSame($phoneEncoder->encode($expected), $actual);
            }
        );
        $this->assertSame($expected->hasGraveyard(), $actual['hasGraveyard']);
        $this->assertSame($expected->note(), $actual['note']);
        $this->assertSame($expected->result()->name, $actual['result']);
    }
}
