<?php

namespace CQRSJobManager\Infrastructure\Factory\EventProcessing;

use CQRSJobManager\EventProcessor;
use CQRSJobManager\Job\JobWorker;
use CQRSJobManager\JobExecutor;
use CQRSJobManager\ProcessManager;
use Doctrine\ORM\EntityManagerInterface;
use Interop\Container\ContainerInterface;

final class JobExecutorFactory
{
    public function __invoke(ContainerInterface $container) : JobExecutor
    {
        /** @var ProcessManager $processManager */
        $processManager = $container->get(ProcessManager::class);

        /** @var EventProcessor $eventProcessor */
        $eventProcessor = $container->get(EventProcessor::class);

        /** @var EntityManagerInterface $cqrsEntityManager */
        $cqrsEntityManager = $container->get('doctrine.entitymanager.cqrs');
        $jobWorkerRepository = $cqrsEntityManager->getRepository(JobWorker::class);

        return new JobExecutor($processManager, $eventProcessor, $jobWorkerRepository);
    }
}
