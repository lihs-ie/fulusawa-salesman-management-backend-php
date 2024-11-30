<?php

namespace App\Domains\Common\ValueObjects;

/**
 * 電話番号を表す値オブジェクト
 */
class PhoneNumber
{
    public const VALID_AREA_CODE_PATTERN = '/^0\d{1,4}$/';

    public const VALID_LOCAL_CODE_PATTERN = '/^\d{1,4}$/';

    public const VALID_SUBSCRIBER_NUMBER_PATTERN = '/^\d{3,5}$/';


    public function __construct(
        public readonly string $areaCode,
        public readonly string $localCode,
        public readonly string $subscriberNumber
    ) {
        if (!preg_match(self::VALID_AREA_CODE_PATTERN, $areaCode)) {
            throw new \InvalidArgumentException('Area code is not valid');
        }

        if (!preg_match(self::VALID_LOCAL_CODE_PATTERN, $localCode)) {
            throw new \InvalidArgumentException('Local code is not valid');
        }

        if (!preg_match(self::VALID_SUBSCRIBER_NUMBER_PATTERN, $subscriberNumber)) {
            throw new \InvalidArgumentException('Subscriber number is not valid');
        }
    }

    public function areaCode(): string
    {
        return $this->areaCode;
    }

    public function localCode(): string
    {
        return $this->localCode;
    }

    public function subscriberNumber(): string
    {
        return $this->subscriberNumber;
    }

    public function equals(?PhoneNumber $other): bool
    {
        if (\is_null($other)) {
            return false;
        }

        if ($this->areaCode !== $other->areaCode) {
            return false;
        }

        if ($this->localCode !== $other->localCode) {
            return false;
        }

        if ($this->subscriberNumber !== $other->subscriberNumber) {
            return false;
        }

        return true;
    }

    public function __toString(): string
    {
        return \sprintf(
            '%s-%s-%s',
            $this->areaCode,
            $this->localCode,
            $this->subscriberNumber
        );
    }
}
