<?php

namespace Tests\Support\Assertions;

use PHPUnit\Framework\Constraint;

/**
 * アサーションに使用する制約を生成する機能.
 */
trait Constrainable
{
    /**
     * 配列が指定したサブセットを持つことの制約を生成するヘルパ.
     */
    protected function arrayContains(array $subset): Constraint\Callback
    {
        return new Constraint\Callback(function ($input) use ($subset): bool {
            if (!\is_array($input)) {
                return false;
            }

            foreach ($subset as $key => $value) {
                if (!\array_key_exists($key, $input)) {
                    return false;
                }

                if ($input[$key] !== $value) {
                    return false;
                }
            }

            return true;
        });
    }
}
