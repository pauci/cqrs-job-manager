<?php

namespace CQRSJobManager\Infrastructure\Doctrine\Type;

use CQRSJobManager\Job\JobName;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class JobNameType extends StringType
{
    const NAME = 'job_name';

    public function getName() : string
    {
        return self::NAME;
    }

    /**
     * @param mixed $value
     * @param AbstractPlatform $platform
     * @return JobName|null
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return null !== $value ? JobName::fromString($value) : null;
    }

    /**
     * @param mixed $value
     * @param AbstractPlatform $platform
     * @return string|mixed
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value instanceof JobName) {
            return (string) $value;
        }
        return $value;
    }
}
