<?php

namespace App\Infrastructures\Common;

use Illuminate\Database\QueryException;
use PDOException;

/**
 * Eloquentを使用するリポジトリの抽象クラス.
 */
abstract class AbstractEloquentRepository
{
    /**
     * データベースエラーコード: 一意制約違反.
     */
    protected const UNIQUE_CONSTRAINT_CODE = '23505';

    /**
     * データベースエラーコード: 外部キー制約違反.
     */
    protected const FOREIGN_KEY_CONSTRAINT_CODE = '23503';

    /**
     * デフォルトのエラーメッセージフォーマット.
     */
    protected const DEFAULT_MESSAGE_FORMATS = [
      self::UNIQUE_CONSTRAINT_CODE => 'Unique constraint violation: %s',
      self::FOREIGN_KEY_CONSTRAINT_CODE => 'Foreign key constraint violation: %s',
    ];

    /**
     * PDOExceptionが発生した場合の共通例外処理.
     */
    protected function handlePDOException(PDOException $exception, ...$messages): void
    {
        if ($exception instanceof QueryException) {
            $code = $exception->getCode();
            $message = \vsprintf(static::DEFAULT_MESSAGE_FORMATS[$code], $messages);

            throw new \UnexpectedValueException($message);
        }

        throw $exception;
    }
}
