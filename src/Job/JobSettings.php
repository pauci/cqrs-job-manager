<?php

namespace CQRSJobManager\Job;

use Assert\Assertion;
use CQRSJobManager\Command\RunJob;
use CQRS\EventHandling\EventBusInterface;
use CQRS\EventStore\EventStoreInterface;
use CQRS\EventStream\ContinuousEventStream;
use CQRS\EventStream\DelayedEventStream;
use CQRS\EventStream\EventStoreEventStream;
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
     * @ORM\Column
     * @var string
     */
    private $eventStore;

    /**
     * @ORM\Column
     * @var string
     */
    private $eventBus;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    private $delay = 10;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    private $throttling = 500000;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    private $stopOnError = true;

    public function __construct(
        string $eventStore,
        string $eventBus,
        int $delayInterval = null,
        int $throttlingInterval = null,
        bool $stopOnError = null
    ) {
        $this->eventStore = $eventStore;
        $this->eventBus = $eventBus;
        if (null !== $delayInterval) {
            $this->delay = (int) $delayInterval;
        }
        if (null !== $throttlingInterval) {
            $this->throttling = (int) $throttlingInterval;
        }
        if (null !== $stopOnError) {
            $this->stopOnError = (bool) $stopOnError;
        }
    }

    public function override(RunJob $command) : JobSettings
    {
        return new self(
            $command->getEventStore() !== null ? $command->getEventStore() : $this->eventStore,
            $command->getEventBus() !== null ? $command->getEventBus() : $this->eventBus,
            $command->getDelay() !== null ? $command->getDelay() : $this->delay,
            $command->getThrottling() !== null ? $command->getThrottling() : $this->throttling,
            $command->getStopOnError() !== null ? $command->getStopOnError() : $this->stopOnError
        );
    }

    public function createEventStream(
        ContainerInterface $container,
        UuidInterface $previousEventId = null
    ) : DelayedEventStream {
        $eventStore = $this->getEventStore($container);

        return new DelayedEventStream(
            new ContinuousEventStream(
                new EventStoreEventStream($eventStore, $previousEventId),
                $this->throttling
            ),
            $this->delay
        );
    }

    private function getEventStore(ContainerInterface $container) : EventStoreInterface
    {
        $eventStore = $container->get($this->eventStore);
        Assertion::isInstanceOf($eventStore, EventStoreInterface::class);
        return $eventStore;
    }

    public function getEventBus(ContainerInterface $container) : EventBusInterface
    {
        $eventBus = $container->get($this->eventBus);
        Assertion::isInstanceOf($eventBus, EventBusInterface::class);
        return $eventBus;
    }

    public function isStopOnError() : bool
    {
        return $this->stopOnError;
    }

    public function jsonSerialize() : array
    {
        return [
            'eventStore' => $this->eventStore,
            'eventBus' => $this->eventBus,
            'delay' => $this->delay,
            'throttling' => $this->throttling,
            'stopOnError' => $this->stopOnError,
        ];
    }
}
