<?php

namespace App\Exceptions;

use Exception;

/**
 * 指定されたリソースが既に存在する場合の例外
 */
class ConflictException extends Exception
{
    /**
     * コンストラクタ
     */
    public function __construct(string $message = 'Conflict.')
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
