<?php

namespace App\Http\Encoders\Customer;

use App\Domains\Cemetery\ValueObjects\CemeteryIdentifier;
use App\Domains\Customer\Entities\Customer;
use App\Domains\TransactionHistory\ValueObjects\TransactionHistoryIdentifier;
use App\Http\Encoders\Common\AddressEncoder;
use App\Http\Encoders\Common\PhoneNumberEncoder;

/**
 * 顧客エンコーダ.
 */
class CustomerEncoder
{
    /**
     * コンストラクタ.
     *
     * @param AddressEncoder $addressEncoder
     */
    public function __construct(
        private readonly AddressEncoder $addressEncoder,
        private readonly PhoneNumberEncoder $phoneEncoder
    ) {
    }

    /**
     * 顧客をJSONエンコード可能な形式に変換する.
     */
    public function encode(Customer $customer): array
    {
        return [
          'identifier' => $customer->identifier()->value(),
          'name' => [
            'first' => $customer->firstName(),
            'last' => $customer->lastName(),
          ],
          'address' => $this->addressEncoder->encode($customer->address()),
          'phone' => $this->phoneEncoder->encode($customer->phone()),
          'cemeteries' => $customer->cemeteries()
            ->map(fn (CemeteryIdentifier $cemetery): string => $cemetery->value())
            ->all(),
          'transactionHistories' => $customer->transactionHistories()
            ->map(fn (TransactionHistoryIdentifier $transactionHistory): string => $transactionHistory->value())
            ->all(),
        ];
    }
}
