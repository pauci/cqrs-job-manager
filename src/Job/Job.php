<?php

namespace CQRSJobManager\Job;

use CQRSJobManager\Command\CreateJob;
use CQRSJobManager\Command\RunJob;
use CQRSJobManager\JobExecutor;
use CQRSJobManager\ProcessManager;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="CQRSJobManager\Infrastructure\Doctrine\Repository\DoctrineJobRepository")
 * @ORM\Table(name="cqrs_job")
 */
class Job
{
    /**
     * @ORM\Id
     * @ORM\Column(type="job_name", length=100)
     * @var JobName
     */
    private $name;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    private $enabled = false;

    /**
     * @ORM\Embedded(class="JobSettings", columnPrefix=false)
     * @var JobSettings
     */
    private $settings;

    /**
     * @var int
     */
    private $pid;

    private function __construct(JobName $name, JobSettings $settings)
    {
        $this->name = $name;
        $this->settings = $settings;
    }

    public static function create(CreateJob $command) : Job
    {
        $name = $command->getJobName();
        $settings = new JobSettings($name, $name);
        return new self($name, $settings);
    }

    public function run(JobExecutor $jobExecutor, RunJob $command = null)
    {
        $settings = $command ? $this->settings->override($command) : $this->settings;
        $previousEventId = $command ? $command->getPreviousEventId() : null;
        $jobExecutor->run($this->name, $settings, $previousEventId);
    }

    public function maintain(ProcessManager $processManager, JobExecutor $jobExecutor)
    {
        if ($this->enabled) {
            $this->start($processManager, $jobExecutor);
        } else {
            $this->stop($processManager);
        }
    }

    private function start(ProcessManager $processManager, JobExecutor $jobExecutor)
    {
        if ($this->pid !== null) {
            return;
        }

        $this->pid = $processManager->fork(sprintf('Event processing job `%s`', $this->name));
        if ($this->pid === 0) {
            // Start the job in forked process
            $this->run($jobExecutor);
        } else {
            $processManager->installChildExitHandler($this->pid, function ($exitCode) {
                $this->pid = null;

                // Disable job if it has failed
                if ($exitCode !== 0) {
                    $this->enabled = false;
                }
            });
        }
    }

    public function stop(ProcessManager $processManager)
    {
        if ($this->isRunning()) {
            $processManager->kill($this->pid);
        }
    }

    public function isRunning() : bool
    {
        return $this->pid > 0;
    }
}
