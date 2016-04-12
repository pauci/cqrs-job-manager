<?php

namespace CQRSJobManager\Command;

use CQRSJobManager\Job\JobName;

final class CreateJob
{
    /**
     * @var JobName
     */
    private $jobName;

    public function __construct(JobName $jobName)
    {
        $this->jobName = $jobName;
    }

    public function getJobName() : JobName
    {
        return $this->jobName;
    }
}
