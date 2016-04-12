<?php

namespace CQRSJobManager;

use CQRSJobManager\Command\ManageAllJobs;
use CQRSJobManager\Command\ManageJob;
use CQRSJobManager\Command\RunJob;
use CQRSJobManager\Job\Job;
use CQRSJobManager\Job\JobRepository;
use Psr\Log\LoggerInterface;

final class JobManager
{
    /**
     * @var ProcessManager
     */
    private $processManager;

    /**
     * @var JobExecutor
     */
    private $jobExecutor;

    /**
     * @var JobRepository
     */
    private $jobRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $pid;

    /**
     * @var bool
     */
    private $terminate = false;

    public function __construct(
        ProcessManager $processManager,
        JobExecutor $jobExecutor,
        JobRepository $jobRepository,
        LoggerInterface $logger
    ) {
        $this->processManager = $processManager;
        $this->jobExecutor = $jobExecutor;
        $this->jobRepository = $jobRepository;
        $this->logger = $logger;
    }

    public function run(RunJob $command)
    {
        $job = $this->jobRepository->get($command->getJobName());
        $job->run($this->jobExecutor, $command);
    }

    public function manage(ManageJob $command)
    {
        $jobName = $command->getJobName();
        $job = $this->jobRepository->get($jobName);

        $this->doManage([$job]);
    }

    public function manageAll(ManageAllJobs $command)
    {
        $jobs = $this->jobRepository->getAll();

        $this->doManage($jobs);
    }

    /**
     * @param Job[] $jobs
     */
    private function doManage(array $jobs)
    {
        $this->pid = $this->processManager->getMyPid();

        $this->processManager->installTerminationSignalHandler(function () {
            $this->terminate = true;
        });

        while (!$this->terminate) {
            $this->maintain($jobs);

            if ($this->isChildProcess()) {
                return;
            }
        }

        $this->terminate($jobs);

    }

    /**
     * @param Job[] $jobs
     */
    private function maintain(array $jobs)
    {
        foreach ($jobs as $job) {
            $job->maintain($this->processManager, $this->jobExecutor);

            if ($this->isChildProcess()) {
                return;
            }
        }

        sleep(1); // sleep is interrupted by signal
        $this->processManager->dispatchSignals();

        $this->jobRepository->flush();
        $this->jobRepository->refreshAll();
    }

    /**
     * @return bool
     */
    private function isChildProcess()
    {
        return $this->processManager->getMyPid() !== $this->pid;
    }

    /**
     * @param Job[] $jobs
     */
    private function terminate(array $jobs)
    {
        // Stop all jobs
        foreach ($jobs as $job) {
            $job->stop($this->processManager);
        }

        // Wait until all jobs are terminated
        foreach ($jobs as $job) {
            while ($job->isRunning()) {
                sleep(1); // sleep is interrupted by signal
                $this->processManager->dispatchSignals();
            }
        }
    }
}
