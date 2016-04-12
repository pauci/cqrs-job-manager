<?php

namespace CQRSJobManager\Command;

use Assert\Assertion;
use CQRSJobManager\Job\JobName;

final class ManageJob
{
    /**
     * @var JobName
     */
    private $jobName;

    public static function fromParams(array $params) : ManageJob
    {
        Assertion::keyExists($params, 'jobName');

        return new self(
            JobName::fromString($params['jobName'])
        );
    }

    public function __construct(JobName $jobName)
    {
        $this->jobName = $jobName;
    }

    public function getJobName() : JobName
    {
        return $this->jobName;
    }
}
