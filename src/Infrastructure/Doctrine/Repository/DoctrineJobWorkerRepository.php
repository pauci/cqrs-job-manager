<?php

namespace CQRSJobManager\Infrastructure\Doctrine\Repository;

use CQRSJobManager\Job\JobName;
use CQRSJobManager\Job\JobWorker;
use CQRSJobManager\Job\JobWorkerRepository;
use Doctrine\ORM\EntityRepository;
use InvalidArgumentException;

final class DoctrineJobWorkerRepository extends EntityRepository implements JobWorkerRepository
{
    public function get(JobName $jobName) : JobWorker
    {
        $jobWorker = $this->find($jobName);
        if (!$jobWorker) {
            throw new InvalidArgumentException(sprintf('Job worker %s not found', $jobName));
        }
        return $jobWorker;
    }

    public function save(JobWorker $job)
    {
        $this->getEntityManager()->flush($job);
    }
}
