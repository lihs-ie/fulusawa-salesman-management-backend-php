<?php

namespace Tests\Unit\Http\Controllers\API;

use App\Domains\Feedback\Entities\Feedback;
use App\Domains\Feedback\ValueObjects\Criteria\Sort;
use App\Exceptions\ConflictException;
use App\Http\Controllers\API\FeedbackController;
use App\Http\Encoders\Feedback\FeedbackEncoder;
use App\Http\Requests\API\Feedback\AddRequest;
use App\Http\Requests\API\Feedback\FindRequest;
use App\Http\Requests\API\Feedback\ListRequest;
use App\Http\Requests\API\Feedback\UpdateRequest;
use App\UseCases\Feedback as UseCase;
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
 * @group feedback
 *
 * @coversNothing
 */
class FeedbackControllerTest extends TestCase
{
    use DependencyBuildable;
    use RequestGeneratable;

    /**
     * テストに使用するインスタンス.
     */
    private Enumerable $instances;

    /**
     * フィードバックエンコーダ.
     */
    private FeedbackEncoder $encoder;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->instances = clone $this->createInstances();
        $this->encoder = $this->builder()->create(FeedbackEncoder::class);
    }

    /**
     * @testdox testAddSuccessReturnsResponse addメソッドに正常な値を与えたとき正常系のレスポンスが返却されること.
     */
    public function testAddSuccessReturnsResponse(): void
    {
        $feedback = $this->builder()->create(Feedback::class);

        $payload = $this->encoder->encode($feedback);

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('add')
          ->with(...$payload);

        $controller = new FeedbackController();

        $request = $this->createJsonRequest(
            class: AddRequest::class,
            payload: $payload,
        );

        $actual = $controller->add($request, $useCase);

        $this->assertInstanceOf(Response::class, $actual);
        $this->assertSame(Response::HTTP_CREATED, $actual->getStatusCode());
    }

    /**
     * @testdox testAddThrowsBadRequestWithInvalidArgumentException createメソッドに不正な値を与えたときBadRequestHttpExceptionがスローされること.
     */
    public function testAddThrowsBadRequestWithInvalidArgumentException(): void
    {
        $feedback = $this->builder()->create(Feedback::class);

        $payload = [
          'identifier' => $feedback->identifier()->value(),
          'type' => $feedback->type()->name,
          'status' => $feedback->status()->name,
          'content' => $feedback->content(),
          'createdAt' => $feedback->createdAt()->toAtomString(),
          'updatedAt' => $feedback->updatedAt()->toAtomString(),
        ];

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('add')
          ->with(...$payload)
          ->willThrowException(new \InvalidArgumentException());

        $controller = new FeedbackController();

        $request = $this->createJsonRequest(
            class: AddRequest::class,
            payload: $payload,
        );

        $this->expectException(BadRequestHttpException::class);

        $controller->add($request, $useCase);
    }

    /**
     * @testdox testAddThrowsConflictWithConflictExceptionWithDuplicateIdentifier createメソッドで既に存在するフィードバックを指定したときConflictHttpExceptionがスローされること.
     */
    public function testAddThrowsConflictWithConflictExceptionWithDuplicateIdentifier(): void
    {
        $feedback = $this->builder()->create(Feedback::class);

        $payload = [
          'identifier' => $feedback->identifier()->value(),
          'type' => $feedback->type()->name,
          'status' => $feedback->status()->name,
          'content' => $feedback->content(),
          'createdAt' => $feedback->createdAt()->toAtomString(),
          'updatedAt' => $feedback->updatedAt()->toAtomString(),
        ];

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('add')
          ->with(...$payload)
          ->willThrowException(new ConflictException());

        $controller = new FeedbackController();

        $request = $this->createJsonRequest(
            class: AddRequest::class,
            payload: $payload,
        );

        $this->expectException(ConflictHttpException::class);

        $controller->add($request, $useCase);
    }

    /**
     * @testdox testUpdateSuccessReturnsResponse updateメソッドに正常な値を与えたとき正常系のレスポンスが返却されること.
     */
    public function testUpdateSuccessReturnsResponse(): void
    {
        $feedback = $this->builder()->create(Feedback::class);

        $payload = $this->encoder->encode($feedback);

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('update')
          ->with(...$payload);

        $controller = new FeedbackController();

        $request = $this->createJsonRequest(
            class: UpdateRequest::class,
            payload: $payload,
            routeParameters: ['identifier' => $feedback->identifier()->value()],
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
        $feedback = $this->builder()->create(Feedback::class);

        $payload = $this->encoder->encode($feedback);

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('update')
          ->with(...$payload)
          ->willThrowException(new \InvalidArgumentException());

        $controller = new FeedbackController();

        $request = $this->createJsonRequest(
            class: UpdateRequest::class,
            payload: $payload,
            routeParameters: ['identifier' => $feedback->identifier()->value()],
        );

        $this->expectException(BadRequestHttpException::class);

        $controller->update($request, $useCase);
    }

    /**
     * @testdox testUpdateThrowsNotFoundWithOutOfBoundsException updateメソッドで存在しないフィードバックを指定したときNotFoundHttpExceptionがスローされること.
     */
    public function testUpdateThrowsNotFoundWithOutOfBoundsException(): void
    {
        $feedback = $this->builder()->create(Feedback::class);

        $payload = $this->encoder->encode($feedback);

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('update')
          ->with(...$payload)
          ->willThrowException(new \OutOfBoundsException());

        $controller = new FeedbackController();

        $request = $this->createJsonRequest(
            class: UpdateRequest::class,
            payload: $payload,
            routeParameters: ['identifier' => $feedback->identifier()->value()],
        );

        $this->expectException(NotFoundHttpException::class);

        $controller->update($request, $useCase);
    }

    /**
     * @testdox testFindSuccessReturnsResponse findメソッドに正常な値を与えたとき正常系のレスポンスが返却されること.
     */
    public function testFindSuccessReturnsResponse(): void
    {
        $feedback = $this->instances->random();

        $expected = $this->encoder->encode($feedback);

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('find')
          ->with($feedback->identifier()->value())
          ->willReturn($feedback);

        $controller = new FeedbackController();

        $request = $this->createGetRequest(
            class: FindRequest::class,
            routeParameters: ['identifier' => $feedback->identifier()->value()],
        );

        $actual = $controller->find($request, $useCase, $this->encoder);

        $this->assertSame($expected, $actual);
    }

    /**
     * @testdox testFindThrowsBadRequestWithInvalidArgumentException findメソッドに不正な値を与えたときBadRequestHttpExceptionがスローされること.
     */
    public function testFindThrowsBadRequestWithInvalidArgumentException(): void
    {
        $feedback = $this->builder()->create(Feedback::class);

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('find')
          ->with($feedback->identifier()->value())
          ->willThrowException(new \InvalidArgumentException());

        $controller = new FeedbackController();

        $request = $this->createGetRequest(
            class: FindRequest::class,
            routeParameters: ['identifier' => $feedback->identifier()->value()],
        );

        $this->expectException(BadRequestHttpException::class);

        $controller->find($request, $useCase, $this->encoder);
    }

    /**
     * @testdox testFindThrowsNotFoundWithOutOfBoundsException findメソッドで存在しないフィードバックを指定したときNotFoundHttpExceptionがスローされること.
     */
    public function testFindThrowsNotFoundWithOutOfBoundsException(): void
    {
        $feedback = $this->builder()->create(Feedback::class);

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('find')
          ->with($feedback->identifier()->value())
          ->willThrowException(new \OutOfBoundsException());

        $controller = new FeedbackController();

        $request = $this->createGetRequest(
            class: FindRequest::class,
            routeParameters: ['identifier' => $feedback->identifier()->value()],
        );

        $this->expectException(NotFoundHttpException::class);

        $controller->find($request, $useCase, $this->encoder);
    }

    /**
     * @testdox testListSuccessReturnsResponseWithEmptyCondition listメソッドに正常な値を与えたとき正常系のレスポンスが返却されること.
     * @dataProvider provideConditions
     */
    public function testListSuccessReturnsResponse(\Closure $closure): void
    {
        $conditions = $closure($this);

        $expecteds = $this->createListExpected($conditions);

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('list')
          ->with($conditions)
          ->willReturn($expecteds);

        $controller = new FeedbackController();

        $request = $this->createGetRequest(
            class: ListRequest::class,
            query: $conditions
        );

        $actuals = $controller->list($request, $useCase, $this->encoder);

        $this->assertCount($expecteds->count(), $actuals['feedbacks']);

        $expecteds
          ->zip(Collection::make($actuals['feedbacks']))
          ->eachSpread(function (?Feedback $expected, ?array $actual): void {
              $this->assertNotNull($expected);
              $this->assertNotNull($actual);
              $this->assertEntity($expected, $actual);
          });
    }

    /**
     * 検索条件を提供するプロバイダ.
     */
    public static function provideConditions(): \Generator
    {
        yield 'empty' => [fn (): array => []];

        yield 'status' => [fn (self $self): array => [
          'status' => $self->instances->random()->status()->name
        ]];

        yield 'type' => [fn (self $self): array => [
          'type' => $self->instances->random()->type()->name
        ]];

        yield 'sort' => [fn (self $self): array => [
          'sort' => $self->builder()->create(Sort::class)->name
        ]];

        yield 'full' => [fn (self $self): array => [
          'status' => $self->instances->random()->status()->name,
          'type' => $self->instances->random()->type()->name,
          'sort' => $self->builder()->create(Sort::class)->name
        ]];
    }

    /**
     * @testdox testListThrowsBadRequestWithInvalidArgumentException listメソッドに不正な値を与えたときBadRequestHttpExceptionがスローされること.
     */
    public function testListThrowsBadRequestWithInvalidArgumentException(): void
    {
        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('list')
          ->willThrowException(new \InvalidArgumentException());

        $controller = new FeedbackController();

        $request = $this->createGetRequest(class: ListRequest::class);

        $this->expectException(BadRequestHttpException::class);

        $controller->list($request, $useCase, $this->encoder);
    }

    /**
     * テストに使用するインスタンスを生成するへルパ.
     */
    private function createInstances(): Enumerable
    {
        return $this->builder()->createList(
            class: Feedback::class,
            count: \mt_rand(5, 10),
        );
    }

    /**
     * 一覧取得の期待値を生成するへルパ.
     */
    private function createListExpected(array $condition): Enumerable
    {
        $sortBy = fn (Enumerable $instances): Enumerable => match ($condition['sort']) {
            Sort::CREATED_AT_ASC->name => $instances->sortBy('createdAt'),
            Sort::CREATED_AT_DESC->name => $instances->sortByDesc('createdAt'),
            Sort::UPDATED_AT_ASC->name => $instances->sortBy('updatedAt'),
            Sort::UPDATED_AT_DESC->name => $instances->sortByDesc('updatedAt'),
        };

        return  $this->instances
          ->when(isset($condition['status']), fn (Enumerable $instances) => $instances->filter(
              fn (Feedback $feedback): bool => $feedback->status()->name === $condition['status']
          ))
          ->when(isset($condition['type']), fn (Enumerable $instances) => $instances->filter(
              fn (Feedback $feedback): bool => $feedback->type()->name === $condition['type']
          ))
          ->when(
              isset($condition['sort']),
              fn (Enumerable $instances) => $sortBy($instances)
          )
          ->values();
    }

    /**
     * エンティティと配列を比較する.
     */
    private function assertEntity(Feedback $expected, array $actual): void
    {
        $this->assertSame($expected->identifier()->value(), $actual['identifier']);
        $this->assertSame($expected->type()->name, $actual['type']);
        $this->assertSame($expected->status()->name, $actual['status']);
        $this->assertSame($expected->content(), $actual['content']);
        $this->assertSame($expected->createdAt()->toAtomString(), $actual['createdAt']);
        $this->assertSame($expected->updatedAt()->toAtomString(), $actual['updatedAt']);
    }
}
