<?php

namespace Tests\Unit\Http\Controllers\API;

use App\Domains\Schedule\Entities\Schedule;
use App\Domains\Schedule\ValueObjects\ScheduleStatus;
use App\Http\Controllers\API\ScheduleController;
use App\Http\Encoders\Schedule\ScheduleEncoder;
use App\Http\Requests\API\Schedule\AddRequest;
use App\Http\Requests\API\Schedule\DeleteRequest;
use App\Http\Requests\API\Schedule\FindRequest;
use App\Http\Requests\API\Schedule\ListRequest;
use App\Http\Requests\API\Schedule\UpdateRequest;
use App\UseCases\Schedule as UseCase;
use Carbon\CarbonImmutable;
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
 * @group schedule
 *
 * @coversNothing
 */
class ScheduleControllerTest extends TestCase
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
    private ScheduleEncoder $encoder;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->encoder = $this->builder()->create(ScheduleEncoder::class);
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
     * @testdox testAddSuccessReturnsResponse addメソッドに正しい値を与えたときにレスポンスが返ること.
     */
    public function testAddSuccessReturnsResponse(): void
    {
        $instance = $this->builder()->create(Schedule::class);

        $payload = $this->encoder->encode($instance);

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('persist')
          ->with(...$payload);

        $request = $this->createJsonRequest(
            class: AddRequest::class,
            payload: $payload
        );

        $controller = new ScheduleController();

        $actual = $controller->add($request, $useCase);

        $this->assertInstanceOf(Response::class, $actual);
        $this->assertSame(Response::HTTP_CREATED, $actual->getStatusCode());
    }

    /**
     * @testdox testAddThrowsBadRequestWithInvalidArgument addメソッドに不正な値を与えたときにBadRequestHttpExceptionがスローされること.
     */
    public function testAddThrowsBadRequestWithInvalidArgument(): void
    {
        $payload = $this->encoder->encode($this->instances->random());

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('persist')
          ->with(...$payload)
          ->willThrowException(new \InvalidArgumentException());

        $request = $this->createJsonRequest(
            class: AddRequest::class,
            payload: $payload
        );

        $controller = new ScheduleController();

        $this->expectException(BadRequestHttpException::class);

        $controller->add($request, $useCase);
    }

    /**
     * @testdox testAddThrowsBadRequestWithUnexpectedValue addメソッドに不正な値を与えたときにBadRequestHttpExceptionがスローされること.
     */
    public function testAddThrowsBadRequestWithUnexpectedValue(): void
    {
        $payload = $this->encoder->encode($this->instances->random());

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('persist')
          ->with(...$payload)
          ->willThrowException(new \UnexpectedValueException());

        $request = $this->createJsonRequest(
            class: AddRequest::class,
            payload: $payload
        );

        $controller = new ScheduleController();

        $this->expectException(BadRequestHttpException::class);

        $controller->add($request, $useCase);
    }

    /**
     * @testdox testUpdateSuccessReturnsResponse updateメソッドに正しい値を与えたときにレスポンスが返ること.
     */
    public function testUpdateSuccessReturnsResponse(): void
    {
        $instance = $this->builder()->create(Schedule::class);

        $payload = $this->encoder->encode($instance);

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('persist')
          ->with(...$payload);

        $request = $this->createJsonRequest(
            class: UpdateRequest::class,
            payload: $payload,
            routeParameters: ['identifier' => $instance->identifier()->value()]
        );

        $controller = new ScheduleController();

        $actual = $controller->update($request, $useCase);

        $this->assertInstanceOf(Response::class, $actual);
        $this->assertSame(Response::HTTP_NO_CONTENT, $actual->getStatusCode());
    }

    /**
     * @testdox testUpdateThrowsBadRequestWithInvalidArgument updateメソッドに不正な値を与えたときにBadRequestHttpExceptionがスローされること.
     */
    public function testUpdateThrowsBadRequestWithInvalidArgument(): void
    {
        $payload = $this->encoder->encode(
            $this->builder()->create(Schedule::class)
        );

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('persist')
          ->with(...$payload)
          ->willThrowException(new \InvalidArgumentException());

        $request = $this->createJsonRequest(
            class: UpdateRequest::class,
            payload: $payload,
            routeParameters: ['identifier' => $payload['identifier']]
        );

        $controller = new ScheduleController();

        $this->expectException(BadRequestHttpException::class);

        $controller->update($request, $useCase);
    }

    /**
     * @testdox testUpdateThrowsBadRequestWithUnexpectedValue updateメソッドに不正な値を与えたときにBadRequestHttpExceptionがスローされること.
     */
    public function testUpdateThrowsBadRequestWithUnexpectedValue(): void
    {
        $payload = $this->encoder->encode(
            $this->builder()->create(Schedule::class)
        );

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('persist')
          ->with(...$payload)
          ->willThrowException(new \UnexpectedValueException());

        $request = $this->createJsonRequest(
            class: UpdateRequest::class,
            payload: $payload,
            routeParameters: ['identifier' => $payload['identifier']]
        );

        $controller = new ScheduleController();

        $this->expectException(BadRequestHttpException::class);

        $controller->update($request, $useCase);
    }

    /**
     * @testdox testUpdateThrowsNotFoundWithOutOfBoundsException updateメソッドに不正な値を与えたときにNotFoundHttpExceptionがスローされること.
     */
    public function testUpdateThrowsNotFoundWithOutOfBoundsException(): void
    {
        $payload = $this->encoder->encode($this->instances->random());

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('persist')
          ->with(...$payload)
          ->willThrowException(new \OutOfBoundsException());

        $request = $this->createJsonRequest(
            class: UpdateRequest::class,
            payload: $payload,
            routeParameters: ['identifier' => $payload['identifier']]
        );

        $controller = new ScheduleController();

        $this->expectException(NotFoundHttpException::class);

        $controller->update($request, $useCase);
    }

    /**
     * @testdox testFindSuccessReturnsResponse findメソッドに正しい値を与えたときにレスポンスが返ること.
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

        $request = $this->createGetRequest(
            class: FindRequest::class,
            routeParameters: ['identifier' => $expected->identifier()->value()]
        );

        $controller = new ScheduleController();

        $actual = $controller->find($request, $useCase, $this->encoder);

        $this->assertIsArray($actual);
        $this->assertEntity($expected, $actual);
    }

    /**
     * @testdox testListSuccessReturnsResponse listメソッドに正しい値を与えたときにレスポンスが返ること.
     *
     * @dataProvider provideSearchConditions
     */
    public function testListSuccessReturnsResponse(array $conditions): void
    {
        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('list')
          ->with($conditions)
          ->willReturn($this->instances);

        $request = $this->createGetRequest(class: ListRequest::class, query: $conditions);

        $controller = new ScheduleController();

        $actual = $controller->list($request, $useCase, $this->encoder);

        Collection::make($this->instances)
          ->zip(Collection::make($actual['schedules']))
          ->eachSpread(function (?Schedule $expected, ?array $actual): void {
              $this->assertNotNull($expected);
              $this->assertNotNull($actual);
              $this->assertEntity($expected, $actual);
          });
    }

    /**
     * @testdox testListThrowsBadRequestWithInvalidArgument listメソッドに不正な値を与えたときにBadRequestHttpExceptionがスローされること.
     */
    public function testListThrowsBadRequestWithInvalidArgument(): void
    {
        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('list')
          ->willThrowException(new \InvalidArgumentException());

        $request = $this->createGetRequest(class: ListRequest::class);

        $controller = new ScheduleController();

        $this->expectException(BadRequestHttpException::class);

        $controller->list($request, $useCase, $this->encoder);
    }

    /**
     * @testdox testDeleteSuccessReturnsResponse deleteメソッドに正しい値を与えたときにレスポンスが返ること.
     */
    public function testDeleteSuccessReturnsResponse(): void
    {
        $instance = $this->instances->random();

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('delete')
          ->with($instance->identifier()->value());

        $request = $this->createJsonRequest(
            class: DeleteRequest::class,
            payload: [],
            routeParameters: ['identifier' => $instance->identifier()->value()]
        );

        $controller = new ScheduleController();

        $actual = $controller->delete($request, $useCase);

        $this->assertInstanceOf(Response::class, $actual);
        $this->assertSame(Response::HTTP_NO_CONTENT, $actual->getStatusCode());
    }

    /**
     * @testdox testDeleteThrowsBadRequestWithInvalidArgument deleteメソッドに不正な値を与えたときにBadRequestHttpExceptionがスローされること.
     */
    public function testDeleteThrowsBadRequestWithInvalidArgument(): void
    {
        $target = $this->instances->random();

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('delete')
          ->with($target->identifier()->value())
          ->willThrowException(new \InvalidArgumentException());

        $request = $this->createJsonRequest(
            class: DeleteRequest::class,
            payload: [],
            routeParameters: ['identifier' => $target->identifier()->value()]
        );

        $controller = new ScheduleController();

        $this->expectException(BadRequestHttpException::class);

        $controller->delete($request, $useCase);
    }

    /**
     * @testdox testDeleteThrowsNotFoundWithOutOfBoundsException deleteメソッドに存在しない識別子を与えたときにNotFoundHttpExceptionがスローされること.
     */
    public function testDeleteThrowsNotFoundWithOutOfBoundsException(): void
    {
        $target = $this->instances->random();

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('delete')
          ->with($target->identifier()->value())
          ->willThrowException(new \OutOfBoundsException());

        $request = $this->createJsonRequest(
            class: DeleteRequest::class,
            payload: [],
            routeParameters: ['identifier' => $target->identifier()->value()]
        );

        $controller = new ScheduleController();

        $this->expectException(NotFoundHttpException::class);

        $controller->delete($request, $useCase);
    }

    /**
     * 検索条件を提供するプロバイダ.
     */
    public static function provideSearchConditions(): \Generator
    {
        yield 'empty' => [[]];

        yield 'status' => [['status' => ScheduleStatus::IN_COMPLETE->name]];

        yield 'date' => [['date' => [
          'start' => CarbonImmutable::now()->toAtomString(),
          'end' => CarbonImmutable::now()->addDay()->toAtomString()
        ]]];
    }

    /**
     * テストに使用するインスタンスを生成するへルパ.
     */
    private function createInstances(): Enumerable
    {
        return $this->builder()->createList(
            class: Schedule::class,
            count: \mt_rand(5, 10)
        );
    }

    /**
     * エンティティと配列の内容を比較する.
     */
    private function assertEntity(Schedule $expected, array $actual): void
    {
        $this->assertSame($expected->identifier()->value(), $actual['identifier']);
        $this->assertSame($expected->user()->value(), $actual['user']);
        $this->assertSame($expected->customer()?->value(), $actual['customer']);
        $this->assertSame($expected->title(), $actual['title']);
        $this->assertSame($expected->description(), $actual['description']);

        $this->assertIsArray($actual['date']);
        $this->assertSame($expected->date()->start()->toAtomString(), $actual['date']['start']);
        $this->assertSame($expected->date()->end()->toAtomString(), $actual['date']['end']);

        $this->assertSame($expected->status()->name, $actual['status']);
        $this->assertNullOr($expected->repeat(), $actual['repeatFrequency'], function ($expected, $actual) {
            $this->assertSame($expected->type()->name, $actual['type']);
            $this->assertSame($expected->interval(), $actual['interval']);
        });
    }
}
