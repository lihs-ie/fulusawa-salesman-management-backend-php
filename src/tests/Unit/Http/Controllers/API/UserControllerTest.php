<?php

namespace Tests\Unit\Http\Controllers\API;

use App\Domains\User\Entities\User as Entity;
use App\Http\Controllers\API\UserController;
use App\Http\Encoders\Common\AddressEncoder;
use App\Http\Encoders\Common\PhoneNumberEncoder;
use App\Http\Encoders\User\UserEncoder;
use App\Http\Requests\API\User\AddRequest;
use App\Http\Requests\API\User\DeleteRequest;
use App\Http\Requests\API\User\FindRequest;
use App\Http\Requests\API\User\UpdateRequest;
use App\UseCases\User as UseCase;
use Illuminate\Http\Response;
use Illuminate\Support\Enumerable;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Support\DependencyBuildable;
use Tests\Support\Helpers\Http\RequestGeneratable;
use Tests\TestCase;
use Tests\Unit\Http\Requests\API\Support\CommonDomainPayloadGeneratable;

/**
 * @group unit
 * @group http
 * @group controllers
 * @group api
 * @group user
 *
 * @coversNothing
 */
class UserControllerTest extends TestCase
{
    use DependencyBuildable;
    use RequestGeneratable;
    use CommonDomainPayloadGeneratable;

    /**
     * テストに使用するインスタンス.
     */
    private Enumerable|null $instances;

    /**
     * テストに使用するエンコーダ.
     */
    private UserEncoder $encoder;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->encoder = $this->builder()->create(UserEncoder::class);
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
     * @testdox testAddSuccessReturnsResponse addメソッドに正しい値を与えたときレスポンスが返ること.
     */
    public function testAddSuccessReturnsResponse(): void
    {
        $user = $this->builder()->create(Entity::class);
        $payload = $this->createPayload($user);

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('persist')
          ->with(...$payload);

        $controller = new UserController();

        $request = $this->createJsonRequest(
            class: AddRequest::class,
            payload: $payload
        );

        $actual = $controller->add($request, $useCase);

        $this->assertInstanceOf(Response::class, $actual);
        $this->assertSame(Response::HTTP_CREATED, $actual->getStatusCode());
    }

    /**
     * @testdox testAddThrowsBadRequestHttpExceptionWithInvalidArgument addメソッドに不正な値を与えたときBadRequestHttpExceptionがスローされること.
     */
    public function testAddThrowsBadRequestHttpExceptionWithInvalidArgument(): void
    {
        $payload = $this->createPayload($this->instances->first());

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('persist')
          ->with(...$payload)
          ->willThrowException(new \InvalidArgumentException());

        $controller = new UserController();

        $request = $this->createJsonRequest(
            class: AddRequest::class,
            payload: $payload
        );

        $this->expectException(BadRequestHttpException::class);

        $controller->add($request, $useCase);
    }

    /**
     * @testdox testUpdateSuccessReturnsResponse updateメソッドに正しい値を与えたときレスポンスが返ること.
     */
    public function testUpdateSuccessReturnsResponse(): void
    {
        $target = $this->instances->random();
        $payload = $this->createPayload($target);

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('persist')
          ->with(...$payload);

        $controller = new UserController();

        $request = $this->createJsonRequest(
            class: UpdateRequest::class,
            payload: $payload,
            routeParameters: ['identifier' => $target->identifier()->value()]
        );

        $actual = $controller->update($request, $useCase);

        $this->assertInstanceOf(Response::class, $actual);
        $this->assertSame(Response::HTTP_NO_CONTENT, $actual->getStatusCode());
    }

    /**
     * @testdox testUpdateThrowsBadRequestHttpExceptionWithInvalidArgument updateメソッドに不正な値を与えたときBadRequestHttpExceptionがスローされること.
     */
    public function testUpdateThrowsBadRequestHttpExceptionWithInvalidArgument(): void
    {
        $target = $this->instances->random();
        $payload = $this->createPayload($target);

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('persist')
          ->with(...$payload)
          ->willThrowException(new \InvalidArgumentException());

        $controller = new UserController();

        $request = $this->createJsonRequest(
            class: UpdateRequest::class,
            payload: $payload,
            routeParameters: ['identifier' => $target->identifier()->value()]
        );

        $this->expectException(BadRequestHttpException::class);

        $controller->update($request, $useCase);
    }

    /**
     * @testdox testUpdateThrowsNotFoundHttpExceptionWithMissingIdentifier updateメソッドに存在しない識別子を与えたときNotFoundHttpExceptionがスローされること.
     */
    public function testUpdateThrowsNotFoundHttpExceptionWithMissingIdentifier(): void
    {
        $payload = $this->createPayload($this->instances->random());

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('persist')
          ->with(...$payload)
          ->willThrowException(new \OutOfBoundsException());

        $controller = new UserController();

        $request = $this->createJsonRequest(
            class: UpdateRequest::class,
            payload: $payload,
            routeParameters: ['identifier' => $payload['identifier']]
        );

        $this->expectException(NotFoundHttpException::class);

        $controller->update($request, $useCase);
    }

    /**
     * @testdox testFindSuccessReturnsResponse findメソッドに正しい値を与えたときレスポンスが返ること.
     */
    public function testFindSuccessReturnsResponse(): void
    {
        $target = $this->instances->random();

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('find')
          ->with($target->identifier()->value())
          ->willReturn($target);

        $controller = new UserController();

        $request = $this->createJsonRequest(
            class: FindRequest::class,
            payload: [],
            routeParameters: ['identifier' => $target->identifier()->value()]
        );

        $actual = $controller->find($request, $useCase, $this->encoder);

        $this->assertEntity($target, $actual);
    }

    /**
     * @testdox testFindThrowsNotFoundHttpExceptionWithMissingIdentifier findメソッドに存在しない識別子を与えたときNotFoundHttpExceptionがスローされること.
     */
    public function testFindThrowsNotFoundHttpExceptionWithMissingIdentifier(): void
    {
        $missing = Uuid::uuid7()->toString();

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('find')
          ->willThrowException(new \OutOfBoundsException());

        $controller = new UserController();

        $request = $this->createJsonRequest(
            class: FindRequest::class,
            payload: [],
            routeParameters: ['identifier' => $missing]
        );

        $this->expectException(NotFoundHttpException::class);

        $controller->find($request, $useCase, $this->encoder);
    }

    /**
     * @testdox testListSuccessReturnsResponse listメソッドに正しい値を与えたときレスポンスが返ること.
     */
    public function testListSuccessReturnsResponse(): void
    {
        $expected = [
          'users' =>  $this->instances->map(
              fn (Entity $entity): array => $this->encoder->encode($entity)
          )->all()
        ];

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('list')
          ->willReturn($this->instances);

        $controller = new UserController();

        $actual = $controller->list($useCase, $this->encoder);

        $this->assertSame($expected, $actual);
    }

    /**
     * @testdox testDeleteSuccessReturnsResponse deleteメソッドに正しい値を与えたときレスポンスが返ること.
     */
    public function testDeleteSuccessReturnsResponse(): void
    {
        $target = $this->instances->random();

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('delete')
          ->with($target->identifier()->value());

        $controller = new UserController();

        $request = $this->createJsonRequest(
            class: DeleteRequest::class,
            payload: [],
            routeParameters: ['identifier' => $target->identifier()->value()]
        );

        $actual = $controller->delete($request, $useCase);

        $this->assertInstanceOf(Response::class, $actual);
        $this->assertSame(Response::HTTP_NO_CONTENT, $actual->getStatusCode());
    }

    /**
     * @testdox testDeleteThrowsNotFoundHttpExceptionWithMissingIdentifier deleteメソッドに存在しない識別子を与えたときNotFoundHttpExceptionがスローされること.
     */
    public function testDeleteThrowsNotFoundHttpExceptionWithMissingIdentifier(): void
    {
        $missing = Uuid::uuid7()->toString();

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('delete')
          ->willThrowException(new \OutOfBoundsException());

        $controller = new UserController();

        $request = $this->createJsonRequest(
            class: DeleteRequest::class,
            payload: [],
            routeParameters: ['identifier' => $missing]
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
        $encoded = $this->encoder->encode($entity);

        return [
          'identifier' => $encoded['identifier'],
          'name' => $encoded['name'],
          'address' => $encoded['address'],
          'phone' => $encoded['phone'],
          'email' => $encoded['email'],
          'password' => 'Password123!',
          'role' => $encoded['role'],
        ];
    }

    /**
     * エンティティと配列の内容を比較する.
     */
    private function assertEntity(Entity $expected, array $actual): void
    {
        $addressEncoder = $this->builder()->create(AddressEncoder::class);
        $phoneEncoder = $this->builder()->create(PhoneNumberEncoder::class);

        $this->assertSame($expected->identifier()->value(), $actual['identifier']);
        $this->assertSame($expected->firstName(), $actual['name']['first']);
        $this->assertSame($expected->lastName(), $actual['name']['last']);
        $this->assertSame($addressEncoder->encode($expected->address()), $actual['address']);
        $this->assertSame($phoneEncoder->encode($expected->phone()), $actual['phone']);
        $this->assertSame($expected->email()->value(), $actual['email']);
        $this->assertSame($expected->role()->name, $actual['role']);
        $this->assertArrayNotHasKey('password', $actual);
    }
}
