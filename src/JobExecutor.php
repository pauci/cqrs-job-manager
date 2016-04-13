<?php

namespace CQRSJobManager;

use CQRSJobManager\Job\JobName;
use CQRSJobManager\Job\JobSettings;
use CQRSJobManager\Job\JobWorkerRepository;
use Ramsey\Uuid\UuidInterface;

final class JobExecutor
{
    /**
     * @var ProcessManager
     */
    private $processManager;

    /**
     * @var EventProcessor
     */
    private $eventProcessor;

    /**
     * @var JobWorkerRepository
     */
    private $jobWorkerRepository;

    public function __construct(
        ProcessManager $processManager,
        EventProcessor $eventProcessor,
        JobWorkerRepository $jobWorkerRepository
    ) {
        $this->processManager = $processManager;
        $this->eventProcessor = $eventProcessor;
        $this->jobWorkerRepository = $jobWorkerRepository;
    }

    public function run(JobName $jobName, JobSettings $settings, UuidInterface $previousEventId = null)
    {
        $jobWorker = $this->jobWorkerRepository->get($jobName);

        $this->processManager->onTermination([$jobWorker, 'stop']);

        $work = $jobWorker->start($this->eventProcessor, $settings, $previousEventId);
        foreach ($work as $event) {
            $this->jobWorkerRepository->save($jobWorker);
            $this->processManager->dispatchSignals();
        }
        $this->jobWorkerRepository->save($jobWorker);
    }
}
