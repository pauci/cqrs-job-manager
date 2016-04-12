<?php

namespace CQRSJobManager\Job;

interface JobRepository
{
    public function get(JobName $jobName) : Job;

    /**
     * @return Job[]
     */
    public function getAll() : array;

    public function refresh(Job $job);

    public function refreshAll();

    public function flush();
}
