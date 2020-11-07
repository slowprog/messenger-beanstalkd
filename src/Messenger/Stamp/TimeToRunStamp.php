<?php

namespace SlowProg\Beanstalkd\Messenger\Stamp;

use Pheanstalk\Job;
use Symfony\Component\Messenger\Stamp\StampInterface;

class TimeToRunStamp implements StampInterface
{
    /**
     * @var int
     */
    private $ttr;

    /**
     * @param int $ttr
     */
    public function __construct(int $ttr)
    {
        $this->ttr = $ttr;
    }

    /**
     * @return int
     */
    public function getTtr(): int
    {
        return $this->ttr;
    }
}