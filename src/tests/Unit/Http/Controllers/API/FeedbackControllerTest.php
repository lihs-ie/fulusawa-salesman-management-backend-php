<?php

namespace Tests\Unit\Http\Controllers\API;

use App\Domains\Feedback\Entities\Feedback;
use App\Domains\Feedback\ValueObjects\Criteria\Sort;
use App\Http\Controllers\API\FeedbackController;
use App\Http\Encoders\Feedback\FeedbackEncoder;
use App\Http\Requests\API\Feedback\FindRequest;
use App\Http\Requests\API\Feedback\ListRequest;
use App\Http\Requests\API\Feedback\PersistRequest;
use App\UseCases\Feedback as UseCase;
use Illuminate\Http\Response;
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
     * @testdox testCreateSuccessReturnsResponse createメソッドに正常な値を与えたとき正常系のレスポンスが返却されること.
     */
    public function testCreateSuccessReturnsResponse(): void
    {
        $feedback = $this->builder()->create(Feedback::class);

        $parameters = [
          'identifier' => $feedback->identifier()->value(),
          'status' => $feedback->status()->name,
          'type' => $feedback->type()->name,
          'content' => $feedback->content(),
          'createdAt' => $feedback->createdAt()->toAtomString(),
          'updatedAt' => $feedback->updatedAt()->toAtomString(),
        ];

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('persist')
          ->with(
              $parameters['identifier'],
              $parameters['type'],
              $parameters['status'],
              $parameters['content'],
              $parameters['createdAt'],
              $parameters['updatedAt'],
          );

        $controller = new FeedbackController();

        $request = $this->createJsonRequest(
            class: PersistRequest::class,
            payload: $parameters,
        );

        $actual = $controller->create($request, $useCase);

        $this->assertInstanceOf(Response::class, $actual);
        $this->assertSame(Response::HTTP_CREATED, $actual->getStatusCode());
    }

    /**
     * @testdox testCreateThrowsBadRequestWithInvalidArgumentException createメソッドに不正な値を与えたときBadRequestHttpExceptionがスローされること.
     */
    public function testCreateThrowsBadRequestWithInvalidArgumentException(): void
    {
        $feedback = $this->builder()->create(Feedback::class);

        $parameters = [
          'identifier' => $feedback->identifier()->value(),
          'status' => $feedback->status()->name,
          'type' => $feedback->type()->name,
          'content' => $feedback->content(),
          'createdAt' => $feedback->createdAt()->toAtomString(),
          'updatedAt' => $feedback->updatedAt()->toAtomString(),
        ];

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('persist')
          ->with(
              $parameters['identifier'],
              $parameters['type'],
              $parameters['status'],
              $parameters['content'],
              $parameters['createdAt'],
              $parameters['updatedAt'],
          )
          ->willThrowException(new \InvalidArgumentException());

        $controller = new FeedbackController();

        $request = $this->createJsonRequest(
            class: PersistRequest::class,
            payload: $parameters,
        );

        $this->expectException(BadRequestHttpException::class);

        $controller->create($request, $useCase);
    }

    /**
     * @testdox testUpdateSuccessReturnsResponse updateメソッドに正常な値を与えたとき正常系のレスポンスが返却されること.
     */
    public function testUpdateSuccessReturnsResponse(): void
    {
        $feedback = $this->builder()->create(Feedback::class);

        $parameters = [
          'identifier' => $feedback->identifier()->value(),
          'status' => $feedback->status()->name,
          'type' => $feedback->type()->name,
          'content' => $feedback->content(),
          'createdAt' => $feedback->createdAt()->toAtomString(),
          'updatedAt' => $feedback->updatedAt()->toAtomString(),
        ];

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('persist')
          ->with(
              $parameters['identifier'],
              $parameters['type'],
              $parameters['status'],
              $parameters['content'],
              $parameters['createdAt'],
              $parameters['updatedAt'],
          );

        $controller = new FeedbackController();

        $request = $this->createJsonRequest(
            class: PersistRequest::class,
            payload: $parameters,
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

        $parameters = [
          'identifier' => $feedback->identifier()->value(),
          'status' => $feedback->status()->name,
          'type' => $feedback->type()->name,
          'content' => $feedback->content(),
          'createdAt' => $feedback->createdAt()->toAtomString(),
          'updatedAt' => $feedback->updatedAt()->toAtomString(),
        ];

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('persist')
          ->with(
              $parameters['identifier'],
              $parameters['type'],
              $parameters['status'],
              $parameters['content'],
              $parameters['createdAt'],
              $parameters['updatedAt'],
          )
          ->willThrowException(new \InvalidArgumentException());

        $controller = new FeedbackController();

        $request = $this->createJsonRequest(
            class: PersistRequest::class,
            payload: $parameters,
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

        $parameters = [
          'identifier' => $feedback->identifier()->value(),
          'status' => $feedback->status()->name,
          'type' => $feedback->type()->name,
          'content' => $feedback->content(),
          'createdAt' => $feedback->createdAt()->toAtomString(),
          'updatedAt' => $feedback->updatedAt()->toAtomString(),
        ];

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('persist')
          ->with(
              $parameters['identifier'],
              $parameters['type'],
              $parameters['status'],
              $parameters['content'],
              $parameters['createdAt'],
              $parameters['updatedAt'],
          )
          ->willThrowException(new \OutOfBoundsException());

        $controller = new FeedbackController();

        $request = $this->createJsonRequest(
            class: PersistRequest::class,
            payload: $parameters,
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
     * @testdox testListSuccessReturnsResponseWithEmptyCondition listメソッドに空の検索条件を与えたとき正常系のレスポンスが返却されること.
     */
    public function testListSuccessReturnsResponse(): void
    {
        $expected = [
          'feedback' => $this->instances
            ->map(fn (Feedback $feedback): array => $this->encoder->encode($feedback))
            ->values()
            ->all()
        ];

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('list')
          ->with([])
          ->willReturn($this->instances);

        $controller = new FeedbackController();

        $request = $this->createGetRequest(class: ListRequest::class);

        $actual = $controller->list($request, $useCase, $this->encoder);

        $this->assertSame($expected, $actual);
    }

    /**
     * @testdox testListSuccessReturnsResponseWithCondition listメソッドに検索条件を与えたとき正常系のレスポンスが返却されること.
     */
    public function testListSuccessReturnsResponseWithCondition(): void
    {
        $instance = $this->instances->random();

        $sort = $this->builder()->create(Sort::class);

        $sortBy = fn (Enumerable $instances): Enumerable => match ($sort) {
            Sort::CREATED_AT_ASC => $instances->sortBy('createdAt'),
            Sort::CREATED_AT_DESC => $instances->sortByDesc('createdAt'),
            Sort::UPDATED_AT_ASC => $instances->sortBy('updatedAt'),
            Sort::UPDATED_AT_DESC => $instances->sortByDesc('updatedAt'),
        };

        $filtered = $this->instances
          ->filter(fn (Feedback $feedback): bool => $feedback->status() === $instance->status())
          ->filter(fn (Feedback $feedback): bool => $feedback->type() === $instance->type())
          ->pipe($sortBy)
          ->values();

        $expected = [
          'feedback' => $filtered->map(
              fn (Feedback $feedback): array => $this->encoder->encode($feedback)
          )->all()
        ];

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('list')
          ->with(
              [
              'status' => $instance->status()->name,
              'type' => $instance->type()->name,
              'sort' => $sort->name,
        ]
          )
          ->willReturn($filtered);

        $controller = new FeedbackController();

        $request = $this->createGetRequest(
            class: ListRequest::class,
            query: [
            'status' => $instance->status()->name,
            'type' => $instance->type()->name,
            'sort' => $sort->name,
      ],
        );

        $actual = $controller->list($request, $useCase, $this->encoder);

        $this->assertSame($expected, $actual);
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
}
