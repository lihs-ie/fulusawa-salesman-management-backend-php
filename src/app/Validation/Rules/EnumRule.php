<?php

namespace App\Validation\Rules;

use BackedEnum;
use Illuminate\Support\Collection;

/**
 * 列挙型バリデーションの基底ルール.
 */
abstract class EnumRule extends AbstractRule
{
    /**
     * エラーメッセージ.
     */
    protected ?string $message = null;

    /**
     * {@inheritdoc}
     */
    protected function passes($attribute, $value): bool
    {
        $source = $this->source();

        if (!enum_exists($source)) {
            $this->message = 'The target enum class does not exist.';

            return false;
        }

        $implements = class_implements($source);

        if (in_array(BackedEnum::class, $implements)) {
            $target = !\is_null($source::tryFrom($value));

            if (!$target) {
                $this->message = \sprintf(':attribute must be a valid enum value of `%s`.', $source);

                return false;
            }

            return true;
        }

        $candidates = Collection::make($source::cases())
            ->mapWithKeys(
                fn ($case) => [$case->name => $case]
            );

        if (!$candidates->has($value)) {
            $this->message = \sprintf(
                ':attribute must be a valid enum value of `%s`.',
                $candidates->keys()->implode(', ')
            );

            return false;
        }

        return true;
    }

    /**
     * 対象のEnumクラスのFQCNを取得する.
     */
    abstract protected function source(): string;
}
