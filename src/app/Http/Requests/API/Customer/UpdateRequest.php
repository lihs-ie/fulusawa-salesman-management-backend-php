<?php

namespace App\Http\Requests\API\Customer;

use App\Http\Requests\API\AbstractRequest;
use App\Validation\Rules;

/**
 * 顧客更新リクエスト.
 */
class UpdateRequest extends AbstractRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
          'identifier' => ['required', 'uuid'],
          'name' => ['required', 'array'],
          'name.last' => ['required', 'string', 'max:255'],
          'name.first' => ['nullable', 'string', 'max:255'],
          'address' => ['required', new Rules\Common\Address()],
          'phone' => ['required', new Rules\Common\PhoneNumber()],
          'cemeteries' => ['array'],
          'cemeteries.*' => ['string', 'uuid'],
          'transactionHistories' => ['array'],
          'transactionHistories.*' => ['uuid'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function routeParameterNames(): array
    {
        return ['identifier'];
    }
}
