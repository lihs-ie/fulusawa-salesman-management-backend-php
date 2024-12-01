<?php

namespace Tests\Unit\Http\Controllers\API;

use App\Domains\Common\ValueObjects\DateTimeRange;
use App\Domains\DailyReport\Entities\DailyReport as Entity;
use App\Domains\Schedule\ValueObjects\ScheduleIdentifier;
use App\Domains\Visit\ValueObjects\VisitIdentifier;
use App\Exceptions\ConflictException;
use App\Http\Controllers\API\DailyReportController;
use App\Http\Encoders\DailyReport\DailyReportEncoder;
use App\Http\Requests\API\DailyReport\AddRequest;
use App\Http\Requests\API\DailyReport\DeleteRequest;
use App\Http\Requests\API\DailyReport\FindRequest;
use App\Http\Requests\API\DailyReport\ListRequest;
use App\Http\Requests\API\DailyReport\UpdateRequest;
use App\UseCases\DailyReport as UseCase;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
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
     * @testdox testAddSuccessReturnsResponse createメソッドで新規の日報を作成し、レスポンスを返却すること.
     */
    public function testAddSuccessReturnsResponse(): void
    {
        $entity = $this->builder()->create(Entity::class);

        $payload = $this->encoder->encode($entity);

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('add')
            ->with(...$payload);

        $controller = new DailyReportController();

        $request = $this->createJsonRequest(
            class: AddRequest::class,
            payload: $payload
        );

        $actual = $controller->add(
            request: $request,
            useCase: $useCase,
        );

        $this->assertInstanceOf(Response::class, $actual);
        $this->assertSame(Response::HTTP_CREATED, $actual->getStatusCode());
    }

    /**
     * @testdox testAddThrowsBadRequestWithInvalidArgumentException createメソッドで不正な引数が渡されたときBadRequestHttpExceptionをスローすること.
     */
    public function testAddThrowsBadRequestWithInvalidArgumentException(): void
    {
        $payload = $this->encoder->encode($this->instances->random());

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('add')
            ->with(...$payload)
            ->willThrowException(new \InvalidArgumentException());

        $controller = new DailyReportController();

        $request = $this->createJsonRequest(
            class: AddRequest::class,
            payload: $payload
        );

        $this->expectException(BadRequestHttpException::class);

        $controller->add(
            request: $request,
            useCase: $useCase,
        );
    }

    /**
     * @testdox testAddThrowsConflictHttpExceptionWithConflictException createメソッドで日報が既に存在するときConflictHttpExceptionをスローすること.
     */
    public function testAddThrowsConflictHttpExceptionWithConflictException(): void
    {
        $payload = $this->encoder->encode($this->instances->random());

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('add')
            ->with(...$payload)
            ->willThrowException(new ConflictException());

        $controller = new DailyReportController();

        $request = $this->createJsonRequest(
            class: AddRequest::class,
            payload: $payload
        );

        $this->expectException(ConflictHttpException::class);

        $controller->add(
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
            ->method('update')
            ->with(...$payload);

        $controller = new DailyReportController();

        $request = $this->createJsonRequest(
            class: UpdateRequest::class,
            payload: $payload,
            routeParameters: ['identifier' => $entity->identifier()->value()]
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
            ->method('update')
            ->with(...$payload)
            ->willThrowException(new \InvalidArgumentException());

        $controller = new DailyReportController();

        $request = $this->createJsonRequest(
            class: UpdateRequest::class,
            payload: $payload,
            routeParameters: ['identifier' => $payload['identifier']]
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
            ->method('update')
            ->with(...$payload)
            ->willThrowException(new \OutOfBoundsException());

        $controller = new DailyReportController();

        $request = $this->createJsonRequest(
            class: UpdateRequest::class,
            payload: $payload,
            routeParameters: ['identifier' => $payload['identifier']]
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
     * @testdox testListSuccessReturnsEntities listメソッドに正常な値を与えたとき正常なレスポンスを返却すること.
     * @dataProvider provideConditions
     */
    public function testListSuccessReturnsEntities(Closure $closure): void
    {
        $conditions = $closure($this);
        $expecteds = $this->createListExpected($conditions);

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('list')
            ->with($conditions)
            ->willReturn($expecteds);

        $controller = new DailyReportController();

        $request = $this->createGetRequest(class: ListRequest::class, query: $conditions);

        $actual = $controller->list(
            request: $request,
            useCase: $useCase,
            encoder: $this->encoder
        );

        $this->assertIsArray($actual);
        $this->assertCount($expecteds->count(), $actual['dailyReports']);

        $expecteds
            ->zip(Collection::make($actual['dailyReports']))
            ->eachSpread(function (?Entity $expected, ?array $actual): void {
                $this->assertNotNull($expected);
                $this->assertNotNull($actual);
                $this->assertEntity($expected, $actual);
            });
    }

    /**
     * 検索条件を提供するプロパイダ.
     */
    public static function provideConditions(): \Generator
    {
        yield 'empty' => [fn (): array => []];

        yield 'user' => [fn (self $self): array => [
            'user' => $self->instances->random()->user()->value()
        ]];

        yield 'date' => [function (self $self): array {
            $instance = $self->instances->random();

            return [
                'date' => [
                    'start' => $instance->date()->setTime(0, 0, 0)->toAtomString(),
                    'end' => $instance->date()->setTime(23, 59, 59)->toAtomString(),
                ]
            ];
        }];

        yield 'isSubmitted' => [fn (): array => [
            'isSubmitted' => (bool) \mt_rand(0, 1)
        ]];
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

    /**
     * listメソッドの期待値を生成する.
     */
    private function createListExpected(array $conditions): Enumerable
    {
        return $this->instances
            ->when(
                isset($conditions['user']),
                fn (Enumerable $instances): Enumerable => $instances->filter(
                    fn (Entity $instance): bool => $instance->user()->value() === $conditions['user']
                )
            )
            ->when(
                isset($conditions['date']),
                fn (Enumerable $instances) => $instances->filter(
                    function (Entity $instance) use ($conditions): bool {
                        $range = $this->builder()->create(
                            class: DateTimeRange::class,
                            overrides: [
                                'start' => CarbonImmutable::parse($conditions['date']['start']),
                                'end' => CarbonImmutable::parse($conditions['date']['end']),
                            ]
                        );

                        return $range->includes($instance->date());
                    }
                )
            )
            ->when(
                isset($conditions['isSubmitted']),
                fn (Enumerable $instances) => $instances->filter(fn (Entity $instance): bool => $instance->isSubmitted() === $conditions['isSubmitted'])
            )
            ->values();
    }
}
