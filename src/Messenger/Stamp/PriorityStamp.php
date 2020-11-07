<?php

namespace SlowProg\Beanstalkd\Messenger\Stamp;

use Symfony\Component\Messenger\Stamp\StampInterface;

class PriorityStamp implements StampInterface
{
    /**
     * @var int
     */
    private $priority;

    /**
     * @param int $priority
     */
    public function __construct(int $priority)
    {
        $this->priority = $priority;
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }
}