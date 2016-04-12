<?php

namespace CQRSJobManager\Infrastructure\Doctrine\Repository;

use CQRSJobManager\Job\Job;
use CQRSJobManager\Job\JobName;
use CQRSJobManager\Job\JobRepository;
use Doctrine\ORM\EntityRepository;
use InvalidArgumentException;

final class DoctrineJobRepository extends EntityRepository implements JobRepository
{
    /**
     * @var Job[]
     */
    private $jobs = [];

    public function get(JobName $jobName) : Job
    {
        $job = $this->find($jobName);
        if (!$job) {
            throw new InvalidArgumentException(sprintf('Job %s not found', $jobName));
        }
        return $job;
    }

    /**
     * @return Job[]
     */
    public function getAll() : array
    {
        return $this->jobs = $this->findAll();
    }

    public function refresh(Job $job)
    {
        $this->getEntityManager()->refresh($job);
    }

    public function refreshAll()
    {
        $entityManager = $this->getEntityManager();
        foreach ($this->jobs as $job) {
            $entityManager->refresh($job);
        }
    }

    public function flush()
    {
        $this->getEntityManager()->flush();
    }
}
