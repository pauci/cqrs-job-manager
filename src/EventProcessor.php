<?php

namespace CQRSJobManager;

use CQRS\EventStream\EventStreamInterface;
use CQRSJobManager\Job\JobSettings;
use Generator;
use Interop\Container\ContainerInterface;
use Ramsey\Uuid\UuidInterface;

final class EventProcessor
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var GarbageCollector
     */
    private $garbageCollector;

    /**
     * @var EventStreamInterface
     */
    private $eventStream;

    public function __construct(ContainerInterface $container, GarbageCollector $garbageCollector)
    {
        $this->container = $container;
        $this->garbageCollector = $garbageCollector;
    }

    /**
     * @param JobSettings $settings
     * @param UuidInterface $previousEventId
     * @return Generator
     */
    public function process(JobSettings $settings, UuidInterface $previousEventId)
    {
        $eventPublicationProcess = $this->createEventPublicationProcess($settings, $previousEventId);

        foreach ($eventPublicationProcess as $event) {
            yield $event;

            $this->garbageCollector->clear();
        }
    }

    /**
     * @return UuidInterface|null
     */
    public function getLastEventId()
    {
        return $this->eventStream ? $this->eventStream->getLastEventId() : null;
    }

    /**
     * @param JobSettings $settings
     * @param UuidInterface $previousEventId
     * @return Generator
     */
    private function createEventPublicationProcess(JobSettings $settings, UuidInterface $previousEventId)
    {
        $this->eventStream = $settings->createEventStream($this->container, $previousEventId);
        $eventBus = $settings->getEventBus($this->container);

        return $eventBus->publishFromStream($this->eventStream);
    }
}
