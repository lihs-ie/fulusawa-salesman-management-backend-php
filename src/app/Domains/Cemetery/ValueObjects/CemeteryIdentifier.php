<?php

namespace App\Domains\Cemetery\ValueObjects;

use App\Domains\Common\ValueObjects\UniversallyUniqueIdentifier;

/**
 * 墓地識別子を表す値オブジェクト
 */
class CemeteryIdentifier extends UniversallyUniqueIdentifier
{
    public function __construct(string $value)
    {
        parent::__construct($value);
    }
}
