<?php

namespace CQRSJobManager\Job;

use Assert\Assertion;
use CQRS\EventStore\EventStoreInterface;
use CQRS\EventStream\ContinuousEventStream;
use CQRS\EventStream\DelayedEventStream;
use CQRS\EventStream\EventStoreEventStream;
use CQRS\EventStream\EventStreamInterface;
use Doctrine\ORM\Mapping as ORM;
use Interop\Container\ContainerInterface;
use JsonSerializable;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Embeddable
 */
class EventStreamConfig implements JsonSerializable
{
    /**
     * @ORM\Column
     * @var string
     */
    private $eventStore;

    /**
     * @ORM\Column(type="uuid", nullable=true)
     * @var UuidInterface|null
     */
    private $previousEventId;

    /**
     * @ORM\Column(type="integer", options={"unsigned":true})
     * @var int
     */
    private $delaySeconds;

    /**
     * @ORM\Column(type="integer", options={"unsigned":true})
     * @var int
     */
    private $throttleMicroseconds;

    public function __construct(
        string $eventStore = 'cqrs.event_store.cqrs_default',
        UuidInterface $previousEventId = null,
        int $delaySeconds = 0,
        int $throttleMicroseconds = 1000000
    ) {
        Assertion::greaterOrEqualThan($delaySeconds, 0);
        Assertion::greaterThan($throttleMicroseconds, 0);

        $this->eventStore = $eventStore;
        $this->delaySeconds = $delaySeconds;
        $this->throttleMicroseconds = $throttleMicroseconds;
    }

    public function override(
        string $eventStore = null,
        UuidInterface $previousEventId = null,
        int $delaySeconds = null,
        int $throttleMicroseconds = null
    ) : self {
        return new self(
            $eventStore ?? $this->eventStore,
            $previousEventId ?? $this->previousEventId,
            $delaySeconds ?? $this->delaySeconds,
            $throttleMicroseconds ?? $this->throttleMicroseconds
        );
    }

    public function createEventStream(
        ContainerInterface $container,
        UuidInterface $previousEventId = null
    ) : EventStreamInterface {
        $eventStore = $this->retrieveEventStore($container);

        $eventStream = new ContinuousEventStream(
            new EventStoreEventStream($eventStore, $previousEventId ?? $this->previousEventId),
            $this->throttleMicroseconds
        );

        if ($this->delaySeconds > 0) {
            $eventStream = new DelayedEventStream($eventStream, $this->delaySeconds);
        }

        return $eventStream;
    }

    private function retrieveEventStore(ContainerInterface $container) : EventStoreInterface
    {
        return $container->get($this->eventStore);
    }

    public function jsonSerialize() : array
    {
        return [
            'eventStore' => $this->eventStore,
            'previousEventId' => $this->previousEventId,
            'delaySeconds' => $this->delaySeconds,
            'throttleMicroseconds' => $this->throttleMicroseconds,
        ];
    }
}
