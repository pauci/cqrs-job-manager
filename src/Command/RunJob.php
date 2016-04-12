<?php

namespace CQRSJobManager\Command;

use Assert\Assertion;
use CQRSJobManager\Job\JobName;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class RunJob
{
    /**
     * @var JobName
     */
    private $jobName;

    /**
     * @var UuidInterface
     */
    private $previousEventId;

    /**
     * @var string|null
     */
    private $eventStore;

    /**
     * @var string|null
     */
    private $eventBus;

    /**
     * @var int|null
     */
    private $delay;

    /**
     * @var int|null
     */
    private $throttling;

    /**
     * @var bool|null
     */
    private $stopOnError;

    /**
     * @var int|null
     */
    private $limit;

    public static function fromParams(array $params) : RunJob
    {
        Assertion::keyExists($params, 'jobName');

        return new self(
            JobName::fromString($params['jobName']),
            isset($params['previousEventId']) ? Uuid::fromString($params['previousEventId']) : null,
            $params['limit'] ?? null,
            $params['eventStore'] ?? null,
            $params['eventBus'] ?? null,
            $params['delay'] ?? null,
            $params['throttling'] ?? null,
            $params['stopOnError'] ?? null
        );
    }

    public function __construct(
        JobName $jobName,
        UuidInterface $previousEventId = null,
        int $limit = null,
        string $eventStore = null,
        string $eventBus = null,
        int $delay = null,
        int $throttling = null,
        bool $stopOnError = null
    ) {
        $this->jobName = $jobName;
        $this->previousEventId = $previousEventId;
        $this->limit = $limit;
        $this->eventStore = $eventStore;
        $this->eventBus = $eventBus;
        $this->delay = $delay;
        $this->throttling = $throttling;
        $this->stopOnError = $stopOnError;
    }

    public function getJobName() : JobName
    {
        return $this->jobName;
    }

    /**
     * @return UuidInterface|null
     */
    public function getPreviousEventId()
    {
        return $this->previousEventId;
    }

    /**
     * @return int|null
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return null|string
     */
    public function getEventStore()
    {
        return $this->eventStore;
    }

    /**
     * @return null|string
     */
    public function getEventBus()
    {
        return $this->eventBus;
    }

    /**
     * @return int|null
     */
    public function getDelay()
    {
        return $this->delay;
    }

    /**
     * @return int|null
     */
    public function getThrottling()
    {
        return $this->throttling;
    }

    /**
     * @return bool|null
     */
    public function getStopOnError()
    {
        return $this->stopOnError;
    }
}
