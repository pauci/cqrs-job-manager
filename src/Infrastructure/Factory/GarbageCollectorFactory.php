<?php

namespace CQRSJobManager\Infrastructure\Factory\EventProcessing;

use CQRSJobManager\GarbageCollector;
use Doctrine\ORM\EntityManagerInterface;
use Interop\Container\ContainerInterface;
use Monolog\Handler\FingersCrossedHandler;

final class GarbageCollectorFactory
{
    public function __invoke(ContainerInterface $container) : GarbageCollector
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.entitymanager.orm_default');

        /** @var FingersCrossedHandler $fingersCrossedHandler */
        $fingersCrossedHandler = $container->get('monolog.handler.fingerscrossed');

        return new GarbageCollector($entityManager, $fingersCrossedHandler);
    }
}
