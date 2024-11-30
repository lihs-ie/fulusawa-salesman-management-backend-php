<?php

namespace App\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;

/**
 * 連想配列のキーにバリデーションを行うルール.
 */
class AttributeIs extends AbstractRule
{
    /**
     * 適用するバリデーションルール.
     */
    protected Enumerable $rules;

    /**
     * エラーメッセージ.
     */
    protected Enumerable $messages;

    /**
     * コンストラクタ
     */
    public function __construct(iterable $rules)
    {
        $normalizedRules = Collection::make($rules)->each(function ($rule): void {
            if (!($rule instanceof AbstractRule)) {
                throw new \InvalidArgumentException(\sprintf(
                    'Rules can contain only `%s`.',
                    AbstractRule::class
                ));
            }
        });

        if ($normalizedRules->isEmpty()) {
            throw new \InvalidArgumentException('No rules are defined.');
        }

        $this->rules = $normalizedRules;
        $this->messages = new Collection();
    }

    /**
     * {@inheritdoc}
     */
    public function passes($attribute, $value): bool
    {
        $target = Collection::make(\explode('.', $attribute))->last();

        $this->messages = $this->rules
            ->map(function (Rule $rule) use ($attribute, $target): ?array {
                return $rule->passes($attribute, $target) ? null : (array) $rule->message();
            })
            ->filter()
            ->flatten();

        return $this->messages->isEmpty();
    }
}
