<?php

namespace SlowProg\Beanstalkd\Messenger\Stamp;

use Pheanstalk\Job;
use Symfony\Component\Messenger\Stamp\StampInterface;

class JobStamp implements StampInterface
{
    /**
     * @var Job
     */
    private $job;

    /**
     * @param Job $job
     */
    public function __construct(Job $job)
    {
        $this->job = $job;
    }

    /**
     * @return Job
     */
    public function getJob(): Job
    {
        return $this->job;
    }
}