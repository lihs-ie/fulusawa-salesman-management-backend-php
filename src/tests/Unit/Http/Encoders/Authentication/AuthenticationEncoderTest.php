<?php

namespace Tests\Unit\Http\Encoders\Authentication;

use App\Domains\Authentication\Entities\Authentication;
use App\Domains\Authentication\ValueObjects\Token;
use App\Http\Encoders\Authentication\AuthenticationEncoder;
use App\Http\Encoders\Authentication\TokenEncoder;
use Tests\Support\Assertions\NullableValueComparable;
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
class AuthenticationEncoderTest extends TestCase
{
    use DependencyBuildable;
    use NullableValueComparable;

    /**
     * @testdox testInstantiateSuccess 正しい値によってインスタンスが生成できること.
     */
    public function testInstantiateSuccess(): void
    {
        $encoder = new AuthenticationEncoder(
            tokenEncoder: $this->builder()->create(TokenEncoder::class)
        );

        $this->assertInstanceOf(AuthenticationEncoder::class, $encoder);
    }

    /**
     * @testdox testEncodeSuccessReturnsArray encodeメソッドで認証情報をエンコードできること.
     */
    public function testEncodeSuccessReturnsArray(): void
    {
        $authentication1 = $this->builder()->create(Authentication::class);
        $authentication2 = $this->builder()->create(Authentication::class, null, [
          'accessToken' => null,
          'refreshToken' => null,
        ]);

        $encoder = new AuthenticationEncoder(
            tokenEncoder: $this->builder()->create(TokenEncoder::class)
        );

        $actual1 = $encoder->encode($authentication1);
        $actual2 = $encoder->encode($authentication2);

        $this->assertEntity($authentication1, $actual1);
        $this->assertEntity($authentication2, $actual2);
    }

    /**
     * エンティティと戻り値を比較する.
     */
    private function assertEntity(Authentication $expected, array $actual): void
    {
        $this->assertSame($expected->identifier()->value(), $actual['identifier']);

        $tokenEncoder = new TokenEncoder();

        $assertToken = fn (?Token $token, $actual) => $this->assertNullOr(
            $token,
            $actual,
            function (Token $expectedToken, $actualToken) use ($tokenEncoder): void {
                $this->assertSame($tokenEncoder->encode($expectedToken), $actualToken);
            }
        );

        $assertToken($expected->accessToken(), $actual['accessToken']);
        $assertToken($expected->refreshToken(), $actual['refreshToken']);
    }
}
