<?php

namespace Tests\Unit\Http\Requests\API\Support;

use App\Domains\Common\ValueObjects\Prefecture;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * テスト用の共通ドメインを表すペイロード生成するへルパ.
 */
trait CommonDomainPayloadGeneratable
{
    /**
     * 郵便番号を生成する.
     */
    protected function generatePostalCode(array $overrides = []): array
    {
        return [
          'first' => $overrides['postalCode.first'] ?? (string)\mt_rand(100, 999),
          'second' => $overrides['postalCode.second'] ?? (string)\mt_rand(1000, 9999),
        ];
    }

    /**
     * 住所を生成する.
     */
    protected function generateAddress(array $overrides = []): array
    {
        return [
          'postalCode' => $overrides['postalCode'] ?? $this->generatePostalCode(),
          'prefecture' => $overrides['prefecture'] ?? Collection::make(Prefecture::cases())->random()->value,
          'city' => $overrides['city'] ?? Str::random(\mt_rand(1, 255)),
          'street' => $overrides['street'] ?? Str::random(\mt_rand(1, 255)),
          'building' => $overrides['building'] ?? Str::random(\mt_rand(1, 255)),
        ];
    }

    /**
     * 電話番号を生成する.
     */
    protected function generatePhone(array $overrides = []): array
    {
        return [
          'areaCode' => $overrides['areaCode'] ?? '0' . (string)\mt_rand(1, 999),
          'localCode' => $overrides['localCode'] ?? (string)\mt_rand(1000, 9999),
          'subscriberNumber' => $overrides['subscriberNumber'] ?? (string)\mt_rand(1000, 9999),
        ];
    }

    /**
     * メールアドレスを生成する.
     */
    protected function generateEmail(array $overrides = []): string
    {
        return $overrides['email'] ?? \Faker\Factory::create()->email;
    }
}
