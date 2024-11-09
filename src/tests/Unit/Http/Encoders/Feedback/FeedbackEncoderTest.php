<?php

namespace Tests\Unit\Http\Encoders\Feedback;

use App\Domains\Feedback\Entities\Feedback;
use App\Http\Encoders\Feedback\FeedbackEncoder;
use Tests\Support\DependencyBuildable;
use Tests\TestCase;

/**
 * @group unit
 * @group http
 * @group encoders
 * @group feedback
 *
 * @coversNothing
 */
class FeedbackEncoderTest extends TestCase
{
    use DependencyBuildable;

    /**
     * @testdox testInstantiateSuccess インスタンス化が成功すること.
     */
    public function testInstantiateSuccess(): void
    {
        $encoder = new FeedbackEncoder();

        $this->assertInstanceOf(FeedbackEncoder::class, $encoder);
    }

    /**
     * @testdox testEncodeSuccessReturnsArray encodeメソッドでフィードバックをJSONエンコード可能な形式に変換できること.
     */
    public function testEncodeSuccessReturnsArray(): void
    {
        $feedback = $this->builder()->create(Feedback::class);

        $encoder = new FeedbackEncoder();

        $actual = $encoder->encode($feedback);

        $this->assertIsArray($actual);
        $this->assertSame($feedback->identifier()->value(), $actual['identifier']);
        $this->assertSame($feedback->status()->name, $actual['status']);
        $this->assertSame($feedback->type()->name, $actual['type']);
        $this->assertSame($feedback->content(), $actual['content']);
        $this->assertSame($feedback->createdAt()->toAtomString(), $actual['createdAt']);
        $this->assertSame($feedback->updatedAt()->toAtomString(), $actual['updatedAt']);
    }
}
