<?php

namespace Tests\Unit\Http\Encoders\Authentication;

use App\Domains\Authentication\ValueObjects\Token;
use App\Http\Encoders\Authentication\TokenEncoder;
use Tests\Support\DependencyBuildable;
use Tests\TestCase;

/**
 * @group unit
 * @group http
 * @group encoder
 * @group authentication
 *
 * @coversNothing
 */
class TokenEncoderTest extends TestCase
{
    use DependencyBuildable;

    /**
     * @testdox testInstantiateSuccess 正しい値によってインスタンスが生成できること.
     */
    public function testInstantiateSuccess(): void
    {
        $encoder = new TokenEncoder();

        $this->assertInstanceOf(TokenEncoder::class, $encoder);
    }

    /**
     * @testdox testEncodeSuccess encodeメソッドでトークンをエンコードできること.
     */
    public function testEncodeSuccessfulReturnsArray(): void
    {
        $token = $this->builder()->create(Token::class);

        $encoder = new TokenEncoder();

        $actual = $encoder->encode($token);

        $this->assertSame($token->type()->name, $actual['type']);
        $this->assertSame($token->value(), $actual['value']);
        $this->assertSame($token->expiresAt()->toAtomString(), $actual['expiresAt']);
    }
}
