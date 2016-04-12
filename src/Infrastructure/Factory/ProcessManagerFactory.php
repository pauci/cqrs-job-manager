<?php

namespace CQRSJobManager\Infrastructure\Factory\EventProcessing;

use CQRSJobManager\ProcessManager;
use Doctrine\DBAL\Connection;
use Interop\Container\ContainerInterface;

final class ProcessManagerFactory
{
    public function __invoke(ContainerInterface $container) : ProcessManager
    {
        /** @var Connection $connection */
        $connection = $container->get('doctrine.connection.orm_default');

        return new ProcessManager($connection);
    }
}
