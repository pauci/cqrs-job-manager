<?php

namespace CQRSJobManager\Job;

interface JobWorkerRepository
{
    public function get(JobName $jobName) : JobWorker;

    public function save(JobWorker $job);
}
