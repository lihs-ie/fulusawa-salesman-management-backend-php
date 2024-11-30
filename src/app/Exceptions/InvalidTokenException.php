<?php

namespace App\Exceptions;

use Exception;

/**
 * トークンが不正な場合の例外
 */
class InvalidTokenException extends Exception
{
    /**
     * コンストラクタ
     */
    public function __construct(string $message = 'Invalid token.')
    {
        parent::__construct($message);
    }

    /**
     * 例外を文字列に変換する
     */
    public function __toString(): string
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
