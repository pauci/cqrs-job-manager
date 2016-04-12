<?php

namespace CQRSJobManager\Infrastructure\Factory\EventProcessing;

use CQRSJobManager\EventProcessor;
use CQRSJobManager\GarbageCollector;
use Interop\Container\ContainerInterface;

final class EventProcessorFactory
{
    public function __invoke(ContainerInterface $container) : EventProcessor
    {
        /** @var GarbageCollector $garbageCollector */
        $garbageCollector = $container->get(GarbageCollector::class);

        return new EventProcessor($container, $garbageCollector);
    }
}
