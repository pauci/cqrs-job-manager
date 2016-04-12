<?php

namespace CQRSJobManager\Job;

use Assert\Assertion;
use JsonSerializable;

final class JobName implements JsonSerializable
{
    /**
     * @var string
     */
    private $name;

    public static function fromString(string $name) : JobName
    {
        Assertion::string($name);
        return new static($name);
    }

    private function __construct(string $name)
    {
        $this->name = $name;
    }

    public function __toString() : string
    {
        return $this->name;
    }

    public function jsonSerialize() : string
    {
        return $this->name;
    }
}
