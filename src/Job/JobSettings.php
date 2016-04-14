<?php

namespace CQRSJobManager\Job;

use CQRS\EventStream\EventStreamInterface;
use CQRSJobManager\Command\RunJob;
use CQRS\EventHandling\EventBusInterface;
use Doctrine\ORM\Mapping as ORM;
use Interop\Container\ContainerInterface;
use JsonSerializable;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Embeddable
 */
final class JobSettings implements JsonSerializable
{
    /**
     * @ORM\Embedded(class="EventStreamConfig", columnPrefix=false)
     * @var EventStreamConfig
     */
    private $eventStreamConfig;

    /**
     * @ORM\Embedded(class="EventBusConfig", columnPrefix=false)
     * @var EventBusConfig
     */
    private $eventBusConfig;
    
    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    private $stopOnError;

    public function __construct(
        EventStreamConfig $eventStreamConfig,
        EventBusConfig $eventBusConfig,
        bool $stopOnError = true
    ) {
        $this->eventStreamConfig = $eventStreamConfig;
        $this->eventBusConfig = $eventBusConfig;
        $this->stopOnError = $stopOnError;
    }

    public function override(RunJob $command) : JobSettings
    {
        return new self(
            $this->eventStreamConfig->override($command->getEventStore(), null, $command->getDelay(), $command->getThrottling()),
            $this->eventBusConfig->override($command->getEventBus()),
            $command->getStopOnError() ?? $this->stopOnError
        );
    }

    public function createEventStream(
        ContainerInterface $container,
        UuidInterface $previousEventId = null
    ) : EventStreamInterface {
        return $this->eventStreamConfig->createEventStream($container, $previousEventId);
    }

    public function getEventBus(ContainerInterface $container) : EventBusInterface
    {
        return $this->eventBusConfig->getEventBus($container);
    }

    public function isStopOnError() : bool
    {
        return $this->stopOnError;
    }

    public function jsonSerialize() : array
    {
        return [
            'eventStreamConfig' => $this->eventStreamConfig,
            'eventBusConfig' => $this->eventBusConfig,
            'stopOnError' => $this->stopOnError,
        ];
    }
}
