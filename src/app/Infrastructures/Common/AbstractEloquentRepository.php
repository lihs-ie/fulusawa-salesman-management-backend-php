<?php

namespace App\Infrastructures\Common;

use App\Exceptions\ConflictException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;

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
    protected function handlePDOException(\PDOException $exception): void
    {
        if ($exception instanceof QueryException) {
            $code = $exception->getCode();

            $class = match ($code) {
                self::UNIQUE_CONSTRAINT_CODE => ConflictException::class,
                self::FOREIGN_KEY_CONSTRAINT_CODE => \UnexpectedValueException::class,
                default => \RuntimeException::class,
            };

            $message = match ($code) {
                self::UNIQUE_CONSTRAINT_CODE => $this->extractDuplicateFields($exception->getMessage()),
                default => $exception->getMessage(),
            };

            throw new $class($message);
        }

        throw $exception;
    }

    /**
     * 一意制約違反時の重複フィールドを抽出する.
     */
    private function extractDuplicateFields(string $message): string
    {
        $fields = new Collection();

        if (preg_match('/Key \((.+?)\)=\((.+?)\) already exists\./', $message, $matches)) {
            $field = $matches[1];
            $value = $matches[2];

            $fields->put($field, $value);
        }

        return $fields->toJson();
    }
}
