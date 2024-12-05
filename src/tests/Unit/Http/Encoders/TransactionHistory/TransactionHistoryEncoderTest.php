<?php

namespace Tests\Unit\Http\Encoders\TransactionHistory;

use App\Domains\TransactionHistory\Entities\TransactionHistory;
use App\Http\Encoders\TransactionHistory\TransactionHistoryEncoder;
use Illuminate\Support\Str;
use Tests\Support\DependencyBuildable;
use Tests\TestCase;

/**
 * @group unit
 * @group http
 * @group encoders
 * @group transactionhistory
 *
 * @coversNothing
 *
 * @internal
 */
class TransactionHistoryEncoderTest extends TestCase
{
    use DependencyBuildable;

    /**
     * @testdox testInstantiateSuccess 正常な値によってインスタンスを生成できること.
     */
    public function testInstantiateSuccess(): void
    {
        $encoder = new TransactionHistoryEncoder();

        $this->assertInstanceOf(TransactionHistoryEncoder::class, $encoder);
    }

    /**
     * @testdox testEncodeSuccessReturnsArray 正常な値によってエンコードできること.
     *
     * @dataProvider provideDescription
     */
    public function testEncodeSuccessReturnsArray(?string $description): void
    {
        $history = $this->builder()->create(TransactionHistory::class, null, [
            'description' => $description,
        ]);

        $encoder = new TransactionHistoryEncoder();

        $encoded = $encoder->encode($history);

        $this->assertIsArray($encoded);
        $this->assertSame($history->identifier()->value(), $encoded['identifier']);
        $this->assertSame($history->customer()->value(), $encoded['customer']);
        $this->assertSame($history->user()->value(), $encoded['user']);
        $this->assertSame($history->type()->name, $encoded['type']);
        $this->assertSame($history->date()->toDateString(), $encoded['date']);
        $this->assertSame($description, $encoded['description']);
    }

    /**
     * 説明を提供するプロバイダ.
     */
    public static function provideDescription(): \Generator
    {
        yield 'null' => [null];

        yield 'not null' => [Str::random(\mt_rand(1, 1000))];
    }
}
