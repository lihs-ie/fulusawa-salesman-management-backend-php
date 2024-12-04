<?php

namespace App\Domains\TransactionHistory\ValueObjects;

use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\Domains\TransactionHistory\ValueObjects\Criteria\Sort;
use App\Domains\User\ValueObjects\UserIdentifier;

/**
 * 検索条件を表す値オブジェクト
 */
class Criteria
{
  /**
   * コンストラクタ.
   */
  public function __construct(
    public readonly UserIdentifier|null $user,
    public readonly CustomerIdentifier|null $customer,
    public readonly Sort|null $sort,
  ) {}

  /**
   * ユーザー識別子を取得する.
   */
  public function user(): UserIdentifier|null
  {
    return $this->user;
  }

  /**
   * 顧客識別子を取得する.
   */
  public function customer(): CustomerIdentifier|null
  {
    return $this->customer;
  }

  /**
   * ソート条件を取得する.
   */
  public function sort(): Sort|null
  {
    return $this->sort;
  }

  /**
   * ハッシュ値を取得する.
   */
  public function hashCode(): string
  {
    $source = \json_encode([
      'user' => $this->user->value(),
      'customer' => $this->customer->value(),
      'sort' => $this->sort->name,
    ]);

    return \hash('sha256', $source);
  }

  /**
   * 与えられた値が自身と同一か判定する.
   */
  public function equals(?self $comparison): bool
  {
    if (\is_null($comparison)) {
      return false;
    }

    if ($this->hashCode() !== $comparison->hashCode()) {
      return false;
    }

    return true;
  }
}
