<?php

namespace App\Http\Requests\API\Customer;

use App\Http\Requests\API\AbstractRequest;
use App\Validation\Rules;

/**
 * 顧客一覧取得リクエスト.
 */
class ListRequest extends AbstractRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
          'name' => ['nullable', 'string', 'min:1', 'max:255'],
          'phone' => ['nullable', new Rules\Common\PhoneNumber()],
          'postalCode' => ['nullable', new Rules\Common\PostalCode()],
        ];
    }
}
