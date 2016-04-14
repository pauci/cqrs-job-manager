<?php

namespace CQRSJobManager\Job;

use CQRS\EventHandling\EventBusInterface;
use Doctrine\ORM\Mapping as ORM;
use Interop\Container\ContainerInterface;
use JsonSerializable;

/**
 * @ORM\Embeddable
 */
class EventBusConfig implements JsonSerializable
{
    /**
     * @ORM\Column
     * @var string
     */
    private $eventBus;

    public function __construct(string $eventBus = 'cqrs.event_bus.cqrs_default')
    {
        $this->eventBus = $eventBus;
    }

    public function override(string $eventBus = null) : self
    {
        return new self(
            $eventBus ?? $this->eventBus
        );
    }

    public function getEventBus(ContainerInterface $container) : EventBusInterface
    {
        return $container->get($this->eventBus);
    }

    public function jsonSerialize() : array
    {
        return [
            'eventBus' => $this->eventBus,
        ];
    }
}
