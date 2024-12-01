<?php

namespace Tests\Unit\Http\Controllers\API;

use App\Domains\Customer\Entities\Customer;
use App\Exceptions\ConflictException;
use App\Http\Controllers\API\CustomerController;
use App\Http\Encoders\Customer\CustomerEncoder;
use App\Http\Requests\API\Customer\AddRequest;
use App\Http\Requests\API\Customer\DeleteRequest;
use App\Http\Requests\API\Customer\FindRequest;
use App\Http\Requests\API\Customer\ListRequest;
use App\Http\Requests\API\Customer\UpdateRequest;
use App\UseCases\Customer as UseCase;
use Closure;
use Illuminate\Http\Response;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Support\DependencyBuildable;
use Tests\Support\Helpers\Http\RequestGeneratable;
use Tests\Support\Helpers\Infrastructures\Database\FactoryResolvable;
use Tests\TestCase;
use Tests\Unit\Http\Requests\API\Support\CommonDomainPayloadGeneratable;

/**
 * @group feature
 * @group controllers
 * @group api
 * @group customer
 *
 * @coversNothing
 */
class CustomerControllerTest extends TestCase
{
    use CommonDomainPayloadGeneratable;
    use DependencyBuildable;
    use FactoryResolvable;
    use RequestGeneratable;

    /**
     * テストに使用するインスタンス.
     */
    private Enumerable|null $instances;

    /**
     * テストに使用する顧客エンコーダ.
     */
    private CustomerEncoder $encoder;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->instances = $this->createInstances();
        $this->encoder = $this->builder()->create(CustomerEncoder::class);
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
     * @testdox testInstantiateSuccess 正常な値によってインスタンス化できること.
     */
    public function testInstantiateSuccess(): void
    {
        $controller = new CustomerController();

        $this->assertInstanceOf(CustomerController::class, $controller);
    }

    /**
     * @testdox testAddReturnsSuccessfulResponse createメソッドで正常な値が与えられたときに正常なレスポンスが返却されること.
     */
    public function testAddReturnsSuccessfulResponse(): void
    {
        $customer = $this->builder()->create(Customer::class);
        $payload = $this->createPersistPayload($customer);

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('add')
            ->with(
                $payload['identifier'],
                $payload['name'],
                $payload['address'],
                $payload['phone'],
                $payload['cemeteries'],
                $payload['transactionHistories']
            );

        $controller = new CustomerController();

        $request = $this->createJsonRequest(
            class: AddRequest::class,
            payload: $payload
        );

        $actual = $controller->add(
            request: $request,
            useCase: $useCase
        );

        $this->assertInstanceOf(Response::class, $actual);
        $this->assertSame(Response::HTTP_CREATED, $actual->getStatusCode());
    }

    /**
     * @testdox testAddThrowsBadRequestWhenInvalidArgumentWasThrown createメソッドで不正な引数が与えられたときにBadRequestExceptionがスローされること.
     */
    public function testAddThrowsBadRequestWhenInvalidArgumentWasThrown(): void
    {
        $customer = $this->builder()->create(Customer::class);
        $payload = $this->createPersistPayload($customer);

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('add')
            ->with(
                $payload['identifier'],
                $payload['name'],
                $payload['address'],
                $payload['phone'],
                $payload['cemeteries'],
                $payload['transactionHistories']
            )
            ->willThrowException(new \InvalidArgumentException());

        $controller = new CustomerController();

        $request = $this->createJsonRequest(
            class: AddRequest::class,
            payload: $payload
        );

        $this->expectException(BadRequestException::class);

        $controller->add(
            request: $request,
            useCase: $useCase
        );
    }

    /**
     * @testdox testAddThrowsConflictWhenConflictExceptionWasThrown createメソッドで不正な引数が与えられたときにConflictHttpExceptionがスローされること.
     */
    public function testAddThrowsConflictWhenConflictExceptionWasThrown(): void
    {
        $customer = $this->builder()->create(Customer::class);
        $payload = $this->createPersistPayload($customer);

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('add')
            ->with(
                $payload['identifier'],
                $payload['name'],
                $payload['address'],
                $payload['phone'],
                $payload['cemeteries'],
                $payload['transactionHistories']
            )
            ->willThrowException(new ConflictException());

        $controller = new CustomerController();

        $request = $this->createJsonRequest(
            class: AddRequest::class,
            payload: $payload
        );

        $this->expectException(ConflictHttpException::class);

        $controller->add(
            request: $request,
            useCase: $useCase
        );
    }

    /**
     * @testdox testUpdateReturnsSuccessfulResponse updateメソッドで正常な値が与えられたときに正常なレスポンスが返却されること.
     */
    public function testUpdateReturnsSuccessfulResponse(): void
    {
        $customer = $this->builder()->create(Customer::class);
        $payload = $this->createPersistPayload($customer);

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('update')
            ->with(
                $payload['identifier'],
                $payload['name'],
                $payload['address'],
                $payload['phone'],
                $payload['cemeteries'],
                $payload['transactionHistories']
            );

        $controller = new CustomerController();

        $request = $this->createJsonRequest(
            class: UpdateRequest::class,
            payload: $payload,
            routeParameters: ['identifier' => $customer->identifier()->value()]
        );

        $actual = $controller->update(
            request: $request,
            useCase: $useCase
        );

        $this->assertInstanceOf(Response::class, $actual);
        $this->assertSame(Response::HTTP_NO_CONTENT, $actual->getStatusCode());
    }

    /**
     * @testdox testUpdateThrowsBadRequestWhenInvalidArgumentExceptionWasThrown updateメソッドで不正な引数が与えられたときにBadRequestExceptionがスローされること.
     */
    public function testUpdateThrowsBadRequestWhenInvalidArgumentExceptionWasThrown(): void
    {
        $customer = $this->builder()->create(Customer::class);
        $payload = $this->createPersistPayload($customer);

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('update')
            ->with(
                $payload['identifier'],
                $payload['name'],
                $payload['address'],
                $payload['phone'],
                $payload['cemeteries'],
                $payload['transactionHistories']
            )
            ->willThrowException(new \InvalidArgumentException());

        $controller = new CustomerController();

        $request = $this->createJsonRequest(
            class: UpdateRequest::class,
            payload: $payload,
            routeParameters: ['identifier' => $customer->identifier()->value()]
        );

        $this->expectException(BadRequestException::class);

        $controller->update(
            request: $request,
            useCase: $useCase
        );
    }

    /**
     * @testdox testUpdateThrowsNotFoundWhenOutOfBoundsExceptionWasThrown updateメソッドで不正な引数が与えられたときにNotFoundHttpExceptionがスローされること.
     */
    public function testUpdateThrowsNotFoundWhenOutOfBoundsExceptionWasThrown(): void
    {
        $customer = $this->builder()->create(Customer::class);
        $payload = $this->createPersistPayload($customer);

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('update')
            ->with(
                $payload['identifier'],
                $payload['name'],
                $payload['address'],
                $payload['phone'],
                $payload['cemeteries'],
                $payload['transactionHistories']
            )
            ->willThrowException(new \OutOfBoundsException());

        $controller = new CustomerController();

        $request = $this->createJsonRequest(
            class: UpdateRequest::class,
            payload: $payload,
            routeParameters: ['identifier' => $customer->identifier()->value()]
        );

        $this->expectException(NotFoundHttpException::class);

        $controller->update(
            request: $request,
            useCase: $useCase
        );
    }

    /**
     * @testdox testFindReturnsSuccessfulResponse findメソッドで正常な値が与えられたときに正常なレスポンスが返却されること.
     */
    public function testFindReturnsSuccessfulResponse(): void
    {
        $customer = $this->instances->random();
        $expected = $this->encoder->encode($customer);

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('find')
            ->with($customer->identifier()->value())
            ->willReturn($customer);

        $controller = new CustomerController();

        $request = $this->createGetRequest(
            class: FindRequest::class,
            routeParameters: ['identifier' => $customer->identifier()->value()]
        );

        $actual = $controller->find(
            request: $request,
            useCase: $useCase,
            encoder: $this->encoder
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @testdox testFindThrowsBadRequestWhenInvalidArgumentExceptionWasThrown findメソッドで不正な引数が与えられたときにBadRequestExceptionがスローされること.
     */
    public function testFindThrowsBadRequestWhenInvalidArgumentExceptionWasThrown(): void
    {
        $customer = $this->instances->random();

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('find')
            ->with($customer->identifier()->value())
            ->willThrowException(new \InvalidArgumentException());

        $controller = new CustomerController();

        $request = $this->createGetRequest(
            class: FindRequest::class,
            routeParameters: ['identifier' => $customer->identifier()->value()]
        );

        $this->expectException(BadRequestException::class);

        $controller->find(
            request: $request,
            useCase: $useCase,
            encoder: $this->encoder
        );
    }

    /**
     * @testdox testFindThrowsNotFoundWhenOutOfBoundsExceptionWasThrown findメソッドで不正な引数が与えられたときにNotFoundHttpExceptionがスローされること.
     */
    public function testFindThrowsNotFoundWhenOutOfBoundsExceptionWasThrown(): void
    {
        $customer = $this->instances->random();

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('find')
            ->with($customer->identifier()->value())
            ->willThrowException(new \OutOfBoundsException());

        $controller = new CustomerController();

        $request = $this->createGetRequest(
            class: FindRequest::class,
            routeParameters: ['identifier' => $customer->identifier()->value()]
        );

        $this->expectException(NotFoundHttpException::class);

        $controller->find(
            request: $request,
            useCase: $useCase,
            encoder: $this->encoder
        );
    }

    /**
     * @testdox testListReturnsSuccessfulResponse listメソッドで正常な値が与えられたときに正常なレスポンスが返却されること.
     *
     * @dataProvider provideConditions
     */
    public function testListReturnsSuccessfulResponse(Closure $closure): void
    {
        $conditions = $closure($this);

        $expected = $this->instances
            ->map(fn (Customer $customer): array => $this->encoder->encode($customer))
            ->all();

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('list')
            ->with($conditions)
            ->willReturn($this->instances);

        $request = $this->createGetRequest(
            class: ListRequest::class,
            query: $conditions
        );

        $controller = new CustomerController();

        $actual = $controller->list(
            request: $request,
            useCase: $useCase,
            encoder: $this->encoder
        );

        $this->assertSame($expected, $actual['customers']);
    }

    /**
     * 検索条件を提供するプロバイダ.
     */
    public static function provideConditions(): \Generator
    {
        yield 'empty' => [fn (): array => []];

        yield 'has name' => [
            fn (): array => ['name' => Str::random(\mt_rand(1, 255))]
        ];

        yield 'has postal_code' => [
            fn (self $self): array => ['postal_code' => $self->generatePostalCode()]
        ];

        yield 'has phone' => [
            fn (self $self): array => ['phone' => $self->generatePhone()]
        ];
    }

    /**
     * @testdox testDeleteReturnsSuccessfulResponse deleteメソッドで正常な値が与えられたときに正常なレスポンスが返却されること.
     */
    public function testDeleteReturnsSuccessfulResponse(): void
    {
        $customer = $this->instances->random();

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('delete')
            ->with($customer->identifier()->value());

        $controller = new CustomerController();

        $request = $this->createGetRequest(
            class: DeleteRequest::class,
            routeParameters: ['identifier' => $customer->identifier()->value()]
        );

        $actual = $controller->delete(
            request: $request,
            useCase: $useCase
        );

        $this->assertInstanceOf(Response::class, $actual);
        $this->assertSame(Response::HTTP_NO_CONTENT, $actual->getStatusCode());
    }

    /**
     * @testdox testDeleteThrowsBadRequestWhenInvalidArgumentExceptionWasThrown deleteメソッドで不正な引数が与えられたときにBadRequestExceptionがスローされること.
     */
    public function testDeleteThrowsBadRequestWhenInvalidArgumentExceptionWasThrown(): void
    {
        $customer = $this->instances->random();

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('delete')
            ->with($customer->identifier()->value())
            ->willThrowException(new \InvalidArgumentException());

        $controller = new CustomerController();

        $request = $this->createGetRequest(
            class: DeleteRequest::class,
            routeParameters: ['identifier' => $customer->identifier()->value()]
        );

        $this->expectException(BadRequestException::class);

        $controller->delete(
            request: $request,
            useCase: $useCase
        );
    }

    /**
     * @testdox testDeleteThrowsNotFoundWhenOutOfBoundsExceptionWasThrown deleteメソッドで不正な引数が与えられたときにNotFoundHttpExceptionがスローされること.
     */
    public function testDeleteThrowsNotFoundWhenOutOfBoundsExceptionWasThrown(): void
    {
        $customer = $this->instances->random();

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('delete')
            ->with($customer->identifier()->value())
            ->willThrowException(new \OutOfBoundsException());

        $controller = new CustomerController();

        $request = $this->createGetRequest(
            class: DeleteRequest::class,
            routeParameters: ['identifier' => $customer->identifier()->value()]
        );

        $this->expectException(NotFoundHttpException::class);

        $controller->delete(
            request: $request,
            useCase: $useCase
        );
    }

    /**
     * テストに使用するインスタンスを生成するへルパ.
     */
    private function createInstances(): Enumerable
    {
        return $this->builder()->createList(Customer::class, \mt_rand(5, 10));
    }

    /**
     * ユースケースの作成・更新に使用するペイロードを生成するへルパ.
     */
    private function createPersistPayload(Customer $customer): array
    {
        return $this->encoder->encode($customer);
    }
}
