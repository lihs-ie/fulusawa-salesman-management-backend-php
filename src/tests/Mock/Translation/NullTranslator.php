<?php

namespace Tests\Mock\Translation;

use Illuminate\Contracts\Translation\Translator;

/**
 * 常に空のメッセージを返すTranslator.
 */
class NullTranslator implements Translator
{
    /**
     * {@inheritdoc}
     */
    public function get($key, array $replace = [], $locale = null): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function choice($key, $number, array $replace = [], $locale = null): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getLocale(): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setLocale($locale): void
    {
    }
}
