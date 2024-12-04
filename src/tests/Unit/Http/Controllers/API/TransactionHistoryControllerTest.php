<?php

namespace Tests\Unit\Http\Controllers\API;

use App\Domains\TransactionHistory\Entities\TransactionHistory as Entity;
use App\Domains\TransactionHistory\ValueObjects\Criteria\Sort;
use App\Http\Controllers\API\TransactionHistoryController;
use App\Http\Encoders\TransactionHistory\TransactionHistoryEncoder;
use App\Http\Requests\API\TransactionHistory\AddRequest;
use App\Http\Requests\API\TransactionHistory\DeleteRequest;
use App\Http\Requests\API\TransactionHistory\FindRequest;
use App\Http\Requests\API\TransactionHistory\ListRequest;
use App\Http\Requests\API\TransactionHistory\UpdateRequest;
use App\UseCases\TransactionHistory as UseCase;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Ramsey\Uuid\Uuid;
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
 * @group transactionhistory
 *
 * @coversNothing
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 *
 * @internal
 */
class TransactionHistoryControllerTest extends TestCase
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
    private TransactionHistoryEncoder $encoder;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->encoder = $this->builder()->create(TransactionHistoryEncoder::class);
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
     * @testdox testAddSuccessReturnsResponse addメソッドに正常な値を与えたとき正常なレスポンスが返ること.
     */
    public function testAddSuccessReturnsResponse(): void
    {
        $instance = $this->builder()->create(Entity::class);

        $payload = $this->encoder->encode($instance);

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('add')
            ->with(...$payload)
        ;

        $controller = new TransactionHistoryController();

        $request = $this->createJsonRequest(
            class: AddRequest::class,
            payload: $payload,
            routeParameters: ['identifier' => $instance->identifier()->value()]
        );

        $response = $controller->add($request, $useCase);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
    }

    /**
     * @testdox testAddThrowsBadRequestHttpExceptionWithInvalidArgument addメソッドに不正な値を与えたときBadRequestHttpExceptionがスローされること.
     */
    public function testAddThrowsBadRequestHttpExceptionWithInvalidArgument(): void
    {
        $instance = $this->builder()->create(Entity::class);

        $payload = $this->encoder->encode($instance);

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('add')
            ->with(...$payload)
            ->willThrowException(new \InvalidArgumentException())
        ;

        $controller = new TransactionHistoryController();

        $request = $this->createJsonRequest(
            class: AddRequest::class,
            payload: $payload,
            routeParameters: ['identifier' => $instance->identifier()->value()]
        );

        $this->expectException(BadRequestHttpException::class);

        $controller->add($request, $useCase);
    }

    /**
     * @testdox testAddThrowsBadRequestHttpExceptionWithUnexpectedValue addメソッドに不正な値を与えたときBadRequestHttpExceptionがスローされること.
     */
    public function testAddThrowsBadRequestHttpExceptionWithUnexpectedValue(): void
    {
        $instance = $this->builder()->create(Entity::class);

        $payload = $this->encoder->encode($instance);

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('add')
            ->with(...$payload)
            ->willThrowException(new \UnexpectedValueException())
        ;

        $controller = new TransactionHistoryController();

        $request = $this->createJsonRequest(
            class: AddRequest::class,
            payload: $payload,
            routeParameters: ['identifier' => $instance->identifier()->value()]
        );

        $this->expectException(BadRequestHttpException::class);

        $controller->add($request, $useCase);
    }

    /**
     * @testdox testUpdateSuccessReturnsResponse updateメソッドに正常な値を与えたとき正常なレスポンスが返ること.
     */
    public function testUpdateSuccessReturnsResponse(): void
    {
        $target = $this->instances->random();

        $instance = $this->builder()->create(
            Entity::class,
            null,
            ['identifier' => $target->identifier()]
        );

        $payload = $this->encoder->encode($instance);

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('update')
            ->with(...$payload)
        ;

        $controller = new TransactionHistoryController();

        $request = $this->createJsonRequest(
            class: UpdateRequest::class,
            payload: $payload,
            routeParameters: ['identifier' => $instance->identifier()->value()]
        );

        $response = $controller->update($request, $useCase);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @testdox testUpdateThrowsBadRequestHttpExceptionWithInvalidArgument updateメソッドに不正な値を与えたときBadRequestHttpExceptionがスローされること.
     */
    public function testUpdateThrowsBadRequestHttpExceptionWithInvalidArgument(): void
    {
        $target = $this->instances->random();

        $instance = $this->builder()->create(
            Entity::class,
            null,
            ['identifier' => $target->identifier()]
        );

        $payload = $this->encoder->encode($instance);

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('update')
            ->with(...$payload)
            ->willThrowException(new \InvalidArgumentException())
        ;

        $controller = new TransactionHistoryController();

        $request = $this->createJsonRequest(
            class: UpdateRequest::class,
            payload: $payload,
            routeParameters: ['identifier' => $instance->identifier()->value()]
        );

        $this->expectException(BadRequestHttpException::class);

        $controller->update($request, $useCase);
    }

    /**
     * @testdox testUpdateThrowsBadRequestHttpExceptionWithUnexpectedValue updateメソッドに不正な値を与えたときBadRequestHttpExceptionがスローされること.
     */
    public function testUpdateThrowsBadRequestHttpExceptionWithUnexpectedValue(): void
    {
        $target = $this->instances->random();

        $instance = $this->builder()->create(
            Entity::class,
            null,
            ['identifier' => $target->identifier()]
        );

        $payload = $this->encoder->encode($instance);

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('update')
            ->with(...$payload)
            ->willThrowException(new \UnexpectedValueException())
        ;

        $controller = new TransactionHistoryController();

        $request = $this->createJsonRequest(
            class: UpdateRequest::class,
            payload: $payload,
            routeParameters: ['identifier' => $instance->identifier()->value()]
        );

        $this->expectException(BadRequestHttpException::class);

        $controller->update($request, $useCase);
    }

    /**
     * @testdox testUpdateThrowsNotFoundHttpExceptionWithOutOfBoundsException updateメソッドに存在しない識別子を与えたときNotFoundHttpExceptionがスローされること.
     */
    public function testUpdateThrowsNotFoundHttpExceptionWithOutOfBoundsException(): void
    {
        $target = $this->instances->random();

        $instance = $this->builder()->create(
            Entity::class,
            null,
            ['identifier' => $target->identifier()]
        );

        $payload = $this->encoder->encode($instance);

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('update')
            ->with(...$payload)
            ->willThrowException(new \OutOfBoundsException())
        ;

        $controller = new TransactionHistoryController();

        $request = $this->createJsonRequest(
            class: UpdateRequest::class,
            payload: $payload,
            routeParameters: ['identifier' => $instance->identifier()->value()]
        );

        $this->expectException(NotFoundHttpException::class);

        $controller->update($request, $useCase);
    }

    /**
     * @testdox testListSuccessReturnsResponse listメソッドに正常な値を与えたとき正常なレスポンスが返ること.
     *
     * @dataProvider provideConditions
     */
    public function testListSuccessReturnsResponse(array $conditions): void
    {
        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('list')
            ->with($conditions)
            ->willReturn($this->instances)
        ;

        $request = $this->createGetRequest(class: ListRequest::class, query: $conditions);

        $controller = new TransactionHistoryController();

        $actual = $controller->list($request, $useCase, $this->encoder);

        $this->assertCount($this->instances->count(), $actual['transactionHistories']);

        $this->instances
            ->zip(Collection::make($actual['transactionHistories']))
            ->eachSpread(function (?Entity $expected, ?array $actual): void {
                $this->assertNotNull($expected);
                $this->assertNotNull($actual);
                $this->assertEntity($expected, $actual);
            })
        ;
    }

    /**
     * 検索条件を提供するプロバイダ.
     */
    public static function provideConditions(): \Generator
    {
        yield 'empty' => [[]];

        yield 'customer' => [['customer' => Uuid::uuid7()->toString()]];

        yield 'user' => [['user' => Uuid::uuid7()->toString()]];

        yield 'sort' => [['sort' => Collection::make(Sort::cases())->random()->name]];

        yield 'fulfilled' => [[
            'user' => Uuid::uuid7()->toString(),
            'customer' => Uuid::uuid7()->toString(),
            'sort' => Collection::make(Sort::cases())->random()->name,
        ]];
    }

    /**
     * @testdox testFindSuccessReturnsResponse findメソッドに正常な値を与えたとき正常なレスポンスが返ること.
     */
    public function testFindSuccessReturnsResponse(): void
    {
        $target = $this->instances->random();

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('find')
            ->with($target->identifier()->value())
            ->willReturn($target)
        ;

        $controller = new TransactionHistoryController();

        $request = $this->createGetRequest(
            class: FindRequest::class,
            routeParameters: ['identifier' => $target->identifier()->value()]
        );

        $actual = $controller->find($request, $useCase, $this->encoder);

        $this->assertEntity($target, $actual);
    }

    /**
     * @testdox testFindThrowsBadRequestHttpExceptionWithInvalidArgument findメソッドに不正な値を与えたときBadRequestHttpExceptionがスローされること.
     */
    public function testFindThrowsBadRequestHttpExceptionWithInvalidArgument(): void
    {
        $target = $this->instances->random();

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('find')
            ->with($target->identifier()->value())
            ->willThrowException(new \InvalidArgumentException())
        ;

        $controller = new TransactionHistoryController();

        $request = $this->createGetRequest(
            class: FindRequest::class,
            routeParameters: ['identifier' => $target->identifier()->value()]
        );

        $this->expectException(BadRequestHttpException::class);

        $controller->find($request, $useCase, $this->encoder);
    }

    /**
     * @testdox testFindThrowsNotFoundHttpExceptionWithOutOfBoundsException findメソッドに存在しない識別子を与えたときNotFoundHttpExceptionがスローされること.
     */
    public function testFindThrowsNotFoundHttpExceptionWithOutOfBoundsException(): void
    {
        $target = $this->instances->random();

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('find')
            ->with($target->identifier()->value())
            ->willThrowException(new \OutOfBoundsException())
        ;

        $controller = new TransactionHistoryController();

        $request = $this->createGetRequest(
            class: FindRequest::class,
            routeParameters: ['identifier' => $target->identifier()->value()]
        );

        $this->expectException(NotFoundHttpException::class);

        $controller->find($request, $useCase, $this->encoder);
    }

    /**
     * @testdox testDeleteSuccessReturnsResponse deleteメソッドに正常な値を与えたとき正常なレスポンスが返ること.
     */
    public function testDeleteSuccessReturnsResponse(): void
    {
        $target = $this->instances->random();

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('delete')
            ->with($target->identifier()->value())
        ;

        $controller = new TransactionHistoryController();

        $request = $this->createJsonRequest(
            class: DeleteRequest::class,
            payload: [],
            routeParameters: ['identifier' => $target->identifier()->value()]
        );

        $response = $controller->delete($request, $useCase);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    /**
     * @testdox testDeleteThrowsBadRequestHttpExceptionWithInvalidArgument deleteメソッドに不正な値を与えたときBadRequestHttpExceptionがスローされること.
     */
    public function testDeleteThrowsBadRequestHttpExceptionWithInvalidArgument(): void
    {
        $target = $this->instances->random();

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('delete')
            ->with($target->identifier()->value())
            ->willThrowException(new \InvalidArgumentException())
        ;

        $controller = new TransactionHistoryController();

        $request = $this->createJsonRequest(
            class: DeleteRequest::class,
            payload: [],
            routeParameters: ['identifier' => $target->identifier()->value()]
        );

        $this->expectException(BadRequestHttpException::class);

        $controller->delete($request, $useCase);
    }

    /**
     * @testdox testDeleteThrowsNotFoundHttpExceptionWithOutOfBoundsException deleteメソッドに存在しない識別子を与えたときNotFoundHttpExceptionがスローされること.
     */
    public function testDeleteThrowsNotFoundHttpExceptionWithOutOfBoundsException(): void
    {
        $target = $this->instances->random();

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('delete')
            ->with($target->identifier()->value())
            ->willThrowException(new \OutOfBoundsException())
        ;

        $controller = new TransactionHistoryController();

        $request = $this->createJsonRequest(
            class: DeleteRequest::class,
            payload: [],
            routeParameters: ['identifier' => $target->identifier()->value()]
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
     * エンティティと配列の内容を比較する.
     */
    private function assertEntity(Entity $expected, array $actual): void
    {
        $this->assertSame($expected->identifier()->value(), $actual['identifier']);
        $this->assertSame($expected->customer()->value(), $actual['customer']);
        $this->assertSame($expected->user()->value(), $actual['user']);
        $this->assertSame($expected->type()->name, $actual['type']);
        $this->assertSame($expected->description(), $actual['description']);
        $this->assertSame($expected->date()->toDateString(), $actual['date']);
    }
}
