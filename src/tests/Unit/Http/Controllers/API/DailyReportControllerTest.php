<?php

namespace Tests\Unit\Http\Controllers\API;

use App\Domains\DailyReport\Entities\DailyReport as Entity;
use App\Domains\Schedule\ValueObjects\ScheduleIdentifier;
use App\Domains\Visit\ValueObjects\VisitIdentifier;
use App\Http\Controllers\API\DailyReportController;
use App\Http\Encoders\DailyReport\DailyReportEncoder;
use App\Http\Requests\API\DailyReport\DeleteRequest;
use App\Http\Requests\API\DailyReport\FindRequest;
use App\Http\Requests\API\DailyReport\ListRequest;
use App\Http\Requests\API\DailyReport\PersistRequest;
use App\UseCases\DailyReport as UseCase;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Support\DependencyBuildable;
use Tests\Support\Helpers\Http\RequestGeneratable;
use Tests\TestCase;

/**
 * @group unit
 * @group http
 * @group controllers
 * @group api
 * @group dailyreport
 *
 * @coversNothing
 */
class DailyReportControllerTest extends TestCase
{
    use DependencyBuildable;
    use RequestGeneratable;

    /**
     * テストに使用するインスタンス.
     */
    private Enumerable|null $instances;

    /**
     * テストに使用する日報エンコーダ.
     */
    private DailyReportEncoder $encoder;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->instances = $this->createInstances();
        $this->encoder = $this->builder()->create(DailyReportEncoder::class);
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown(): void
    {
        $this->instances = null;

        parent::tearDown();
    }

    /**
     * @testdox testCreateSuccessReturnsResponse createメソッドで新規の日報を作成し、レスポンスを返却すること.
     */
    public function testCreateSuccessReturnsResponse(): void
    {
        $entity = $this->builder()->create(Entity::class);

        $payload = $this->encoder->encode($entity);

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('persist')
          ->with(
              $payload['identifier'],
              $payload['user'],
              $payload['date'],
              $payload['schedules'],
              $payload['visits'],
              $payload['isSubmitted'],
          );

        $controller = new DailyReportController();

        $request = $this->createJsonRequest(
            class: PersistRequest::class,
            payload: $payload
        );

        $actual = $controller->create(
            request: $request,
            useCase: $useCase,
        );

        $this->assertInstanceOf(Response::class, $actual);
        $this->assertSame(Response::HTTP_CREATED, $actual->getStatusCode());
    }

    /**
     * @testdox testCreateThrowsBadRequestWithInvalidArgumentException createメソッドで不正な引数が渡されたときBadRequestHttpExceptionをスローすること.
     */
    public function testCreateThrowsBadRequestWithInvalidArgumentException(): void
    {
        $payload = $this->encoder->encode($this->instances->random());

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('persist')
          ->with(
              $payload['identifier'],
              $payload['user'],
              $payload['date'],
              $payload['schedules'],
              $payload['visits'],
              $payload['isSubmitted'],
          )
          ->willThrowException(new \InvalidArgumentException());

        $controller = new DailyReportController();

        $request = $this->createJsonRequest(
            class: PersistRequest::class,
            payload: $payload
        );

        $this->expectException(BadRequestHttpException::class);

        $controller->create(
            request: $request,
            useCase: $useCase,
        );
    }

    /**
     * @testdox testUpdateSuccessReturnsResponse updateメソッドで日報を更新し、レスポンスを返却すること.
     */
    public function testUpdateSuccessReturnsResponse(): void
    {
        $entity = $this->builder()->create(Entity::class);

        $payload = $this->encoder->encode($entity);

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('persist')
          ->with(
              $payload['identifier'],
              $payload['user'],
              $payload['date'],
              $payload['schedules'],
              $payload['visits'],
              $payload['isSubmitted'],
          );

        $controller = new DailyReportController();

        $request = $this->createJsonRequest(
            class: PersistRequest::class,
            payload: $payload
        );

        $actual = $controller->update(
            request: $request,
            useCase: $useCase,
        );

        $this->assertInstanceOf(Response::class, $actual);
        $this->assertSame(Response::HTTP_NO_CONTENT, $actual->getStatusCode());
    }

    /**
     * @testdox testUpdateThrowsBadRequestWithInvalidArgumentException updateメソッドで不正な引数が渡されたときBadRequestHttpExceptionをスローすること.
     */
    public function testUpdateThrowsBadRequestWithInvalidArgumentException(): void
    {
        $payload = $this->encoder->encode($this->instances->random());

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('persist')
          ->with(
              $payload['identifier'],
              $payload['user'],
              $payload['date'],
              $payload['schedules'],
              $payload['visits'],
              $payload['isSubmitted'],
          )
          ->willThrowException(new \InvalidArgumentException());

        $controller = new DailyReportController();

        $request = $this->createJsonRequest(
            class: PersistRequest::class,
            payload: $payload
        );

        $this->expectException(BadRequestHttpException::class);

        $controller->update(
            request: $request,
            useCase: $useCase,
        );
    }

    /**
     * @testdox testUpdateThrowsNotFoundWithOutOfBoundsException updateメソッドで指定した日報が存在しないときNotFoundHttpExceptionをスローすること.
     */
    public function testUpdateThrowsNotFoundWithOutOfBoundsException(): void
    {
        $payload = $this->encoder->encode($this->instances->random());

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('persist')
          ->with(
              $payload['identifier'],
              $payload['user'],
              $payload['date'],
              $payload['schedules'],
              $payload['visits'],
              $payload['isSubmitted'],
          )
          ->willThrowException(new \OutOfBoundsException());

        $controller = new DailyReportController();

        $request = $this->createJsonRequest(
            class: PersistRequest::class,
            payload: $payload
        );

        $this->expectException(NotFoundHttpException::class);

        $controller->update(
            request: $request,
            useCase: $useCase,
        );
    }

    /**
     * @testdox testFindSuccessReturnsSuccessfulResponse findメソッドに正常な値を与えたとき正常なレスポンスを返却すること.
     */
    public function testFindSuccessReturnsSuccessfulResponse(): void
    {
        $entity = $this->instances->random();

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('find')
          ->with($entity->identifier()->value())
          ->willReturn($entity);

        $controller = new DailyReportController();

        $request = $this->createGetRequest(
            class: FindRequest::class,
            routeParameters: ['identifier' => $entity->identifier()->value()]
        );

        $actual = $controller->find(
            request: $request,
            useCase: $useCase,
            encoder: $this->encoder
        );

        $this->assertIsArray($actual);
        $this->assertEntity($entity, $actual);
    }

    /**
     * @testdox testFindThrowsBadRequestWithInvalidArgumentException findメソッドで不正な引数が渡されたときBadRequestHttpExceptionをスローすること.
     */
    public function testFindThrowsBadRequestWithInvalidArgumentException(): void
    {
        $entity = $this->instances->random();

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('find')
          ->with($entity->identifier()->value())
          ->willThrowException(new \InvalidArgumentException());

        $controller = new DailyReportController();

        $request = $this->createGetRequest(
            class: FindRequest::class,
            routeParameters: ['identifier' => $entity->identifier()->value()]
        );

        $this->expectException(BadRequestHttpException::class);

        $controller->find(
            request: $request,
            useCase: $useCase,
            encoder: $this->encoder
        );
    }

    /**
     * @testdox testFindThrowsNotFoundWithOutOfBoundsException findメソッドで指定した日報が存在しないときNotFoundHttpExceptionをスローすること.
     */
    public function testFindThrowsNotFoundWithOutOfBoundsException(): void
    {
        $entity = $this->instances->random();

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('find')
          ->with($entity->identifier()->value())
          ->willThrowException(new \OutOfBoundsException());

        $controller = new DailyReportController();

        $request = $this->createGetRequest(
            class: FindRequest::class,
            routeParameters: ['identifier' => $entity->identifier()->value()]
        );

        $this->expectException(NotFoundHttpException::class);

        $controller->find(
            request: $request,
            useCase: $useCase,
            encoder: $this->encoder
        );
    }

    /**
     * @testdox testListSuccessReturnsSuccessfulResponseWithEmptyConditions listメソッドに空の検索条件を与えたとき正常なレスポンスを返却すること.
     */
    public function testListSuccessReturnsSuccessfulResponseWithEmptyConditions(): void
    {
        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('list')
          ->with([])
          ->willReturn($this->instances);

        $controller = new DailyReportController();

        $request = $this->createGetRequest(class: ListRequest::class);

        $actual = $controller->list(
            request: $request,
            useCase: $useCase,
            encoder: $this->encoder
        );

        $this->assertIsArray($actual);

        $this->instances
          ->zip(Collection::make($actual))
          ->eachSpread(function (?Entity $expected, ?array $actual): void {
              $this->assertNotNull($expected);
              $this->assertNotNull($actual);
              $this->assertEntity($expected, $actual);
          });
    }

    /**
     * @testdox testListSuccessReturnsSuccessfulResponseWithConditions listメソッドに検索条件を与えたとき正常なレスポンスを返却すること.
     */
    public function testListSuccessReturnsSuccessfulResponseWithConditions(): void
    {
        $instance = $this->instances->random();

        $user = $instance->user();
        $date = $instance->date();
        $isSubmitted = $instance->isSubmitted();

        $expecteds = $this->instances
          ->filter(fn (Entity $entity): bool => $entity->user()->equals($user))
          ->filter(fn (Entity $entity): bool => $entity->date()->toAtomString() === $date->toAtomString())
          ->filter(fn (Entity $entity): bool => $entity->isSubmitted() === $isSubmitted);

        $request = $this->createGetRequest(
            class: ListRequest::class,
            query: [
            'user' => $user->value(),
            'date' => [
              'start' => $date->toAtomString(),
              'end' => $date->toAtomString(),
            ],
            'isSubmitted' => $isSubmitted,
      ]
        );

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('list')
          ->with($request->all())
          ->willReturn($expecteds);

        $controller = new DailyReportController();

        $actual = $controller->list(
            request: $request,
            useCase: $useCase,
            encoder: $this->encoder
        );

        $this->assertIsArray($actual);
        $expecteds
          ->zip(Collection::make($actual))
          ->eachSpread(function (?Entity $expected, ?array $actual): void {
              $this->assertNotNull($expected);
              $this->assertNotNull($actual);
              $this->assertEntity($expected, $actual);
          });
    }

    /**
     * @testdox testListThrowsBadRequestWithInvalidArgumentException listメソッドで不正な引数が渡されたときBadRequestHttpExceptionをスローすること.
     */
    public function testListThrowsBadRequestWithInvalidArgumentException(): void
    {
        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('list')
          ->willThrowException(new \InvalidArgumentException());

        $controller = new DailyReportController();

        $request = $this->createGetRequest(class: ListRequest::class);

        $this->expectException(BadRequestHttpException::class);

        $controller->list(
            request: $request,
            useCase: $useCase,
            encoder: $this->encoder
        );
    }

    /**
     * @testdox testDeleteSuccessReturnsResponse deleteメソッドで正常な値を与えたとき正常なレスポンスを返却すること.
     */
    public function testDeleteSuccessReturnsResponse(): void
    {
        $entity = $this->instances->random();

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('delete')
          ->with($entity->identifier()->value());

        $controller = new DailyReportController();

        $request = $this->createJsonRequest(
            class: DeleteRequest::class,
            routeParameters: ['identifier' => $entity->identifier()->value()],
            payload: []
        );

        $actual = $controller->delete(
            request: $request,
            useCase: $useCase
        );

        $this->assertInstanceOf(Response::class, $actual);
        $this->assertSame(Response::HTTP_NO_CONTENT, $actual->getStatusCode());
    }

    /**
     * @testdox testDeleteThrowsNotFoundWithOutOfBoundsException deleteメソッドで指定した日報が存在しないときNotFoundHttpExceptionをスローすること.
     */
    public function testDeleteThrowsNotFoundWithOutOfBoundsException(): void
    {
        $entity = $this->instances->random();

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('delete')
          ->with($entity->identifier()->value())
          ->willThrowException(new \OutOfBoundsException());

        $controller = new DailyReportController();

        $request = $this->createJsonRequest(
            class: DeleteRequest::class,
            routeParameters: ['identifier' => $entity->identifier()->value()],
            payload: []
        );

        $this->expectException(NotFoundHttpException::class);

        $controller->delete(
            request: $request,
            useCase: $useCase
        );
    }

    /**
     * テストに使用するインスタンスを生成する.
     */
    private function createInstances(): Enumerable
    {
        return $this->builder()->createList(
            class: Entity::class,
            count: \mt_rand(5, 10)
        );
    }

    /**
     * エンティティと配列を比較する.
     */
    private function assertEntity(Entity $expected, array $actual): void
    {
        $this->assertSame($expected->identifier()->value(), $actual['identifier']);
        $this->assertSame($expected->user()->value(), $actual['user']);
        $this->assertSame($expected->date()->toAtomString(), $actual['date']);

        $expectedSchedules = $expected->schedules()->map(
            fn (ScheduleIdentifier $schedule): string => $schedule->value()
        )->all();
        $this->assertSame($expectedSchedules, $actual['schedules']);

        $expectedVisits = $expected->visits()->map(
            fn (VisitIdentifier $visit): string => $visit->value()
        )->all();
        $this->assertSame($expectedVisits, $actual['visits']);

        $this->assertSame($expected->isSubmitted(), $actual['isSubmitted']);
    }
}
