<?php

namespace CQRSJobManager\Infrastructure\Factory\EventProcessing;

use CQRSJobManager\Job\Job;
use CQRSJobManager\JobExecutor;
use CQRSJobManager\JobManager;
use CQRSJobManager\ProcessManager;
use Doctrine\ORM\EntityManagerInterface;
use Interop\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class JobManagerFactory
{
    public function __invoke(ContainerInterface $container) : JobManager
    {
        /** @var JobExecutor $jobExecutor */
        $jobExecutor = $container->get(JobExecutor::class);

        /** @var ProcessManager $processManager */
        $processManager = $container->get(ProcessManager::class);

        /** @var EntityManagerInterface $cqrsEntityManager */
        $cqrsEntityManager = $container->get('doctrine.entitymanager.cqrs');
        $jobRepository = $cqrsEntityManager->getRepository(Job::class);

        /** @var LoggerInterface $logger */
        $logger = $container->get('monolog.logger.event_processor_manager');

        return new JobManager($processManager, $jobExecutor, $jobRepository, $logger);
    }
}
