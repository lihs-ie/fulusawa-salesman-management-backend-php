<?php

namespace Tests\Unit\Http\Encoders\User;

use App\Domains\User\Entities\User;
use App\Http\Encoders\Common\AddressEncoder;
use App\Http\Encoders\Common\PhoneNumberEncoder;
use App\Http\Encoders\User\UserEncoder;
use Tests\Support\DependencyBuildable;
use Tests\TestCase;

/**
 * @group unit
 * @group http
 * @group encoders
 * @group user
 *
 * @coversNothing
 */
class UserEncoderTest extends TestCase
{
    use DependencyBuildable;

    /**
     * @testdox testInstantiateSuccess 正常な値によってインスタンスを生成できること.
     */
    public function testInstantiateSuccess(): void
    {
        $address = $this->builder()->create(AddressEncoder::class);
        $phone = $this->builder()->create(PhoneNumberEncoder::class);

        $encoder = new UserEncoder($address, $phone);

        $this->assertInstanceOf(UserEncoder::class, $encoder);
    }

    /**
     * @testdox testEncodeSuccessReturnsArray 正常な値によってユーザーをエンコードできること.
     */
    public function testEncodeSuccessReturnsArray(): void
    {
        $user = $this->builder()->create(User::class);

        $address = $this->builder()->create(AddressEncoder::class);
        $phone = $this->builder()->create(PhoneNumberEncoder::class);

        $encoder = new UserEncoder($address, $phone);

        $actual = $encoder->encode($user);

        $this->assertIsArray($actual);
        $this->assertSame($user->identifier()->value(), $actual['identifier']);
        $this->assertSame($user->firstName(), $actual['name']['first']);
        $this->assertSame($user->lastName(), $actual['name']['last']);
        $this->assertSame($address->encode($user->address()), $actual['address']);
        $this->assertSame($phone->encode($user->phone()), $actual['phone']);
        $this->assertSame($user->email()->value(), $actual['email']);
        $this->assertSame($user->role()->name, $actual['role']);
        $this->assertArrayNotHasKey('password', $actual);
    }
}
