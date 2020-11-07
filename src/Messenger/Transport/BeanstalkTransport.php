<?php

namespace SlowProg\Beanstalkd\Messenger\Transport;

use LogicException;
use Pheanstalk\Pheanstalk;
use Pheanstalk\Contract\PheanstalkInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use SlowProg\Beanstalkd\Messenger\Stamp\JobStamp;
use SlowProg\Beanstalkd\Messenger\Stamp\TimeToTunStamp;

class BeanstalkTransport implements TransportInterface
{
    /**
     * @var string
     */
    private const HOST_DEFAULT = 'localhost';

    /**
     * @var integer
     */
    private const PORT_DEFAULT = Pheanstalk::DEFAULT_PORT;

    /**
     * @var string
     */
    private const TUBE_DEFAULT = 'default';

    /**
     * Timeout for waiting for message in seconds for reserve() method.
     *
     * @var integer
     */
    private const RESERVE_TIMEOUT_DEFAULT = 1;

    /**
     * Timeout for stream_socket_client() function.
     *
     * @var integer
     */
    private const CONNECT_TIMEOUT_DEFAULT = 10;

    /**
     * @var PhpSerializer
     */
    private $serializer;

    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var int
     */
    private $reserveTimeout;

    /**
     * @var int
     */
    private $connectTimeout;

    /**
     * @var int
     */
    private $tube;

    /**
     * @var Pheanstalk
     */
    private $connection;

    /**
     * @param string $host
     * @param int    $port
     * @param int    $timeout
     * @param string $tube
     */
    public function __construct(
        ?string $host = self::HOST_DEFAULT,
        ?int $port = self::PORT_DEFAULT,
        ?string $tube = self::TUBE_DEFAULT,
        ?int $reserveTimeout = self::RESERVE_TIMEOUT_DEFAULT,
        ?int $connectTimeout = self::CONNECT_TIMEOUT_DEFAULT
    ) {
        $this->host           = $host ?? self::HOST_DEFAULT;
        $this->port           = $port ?? self::PORT_DEFAULT;
        $this->tube           = $tube ?? self::TUBE_DEFAULT;
        $this->reserveTimeout = $timeout ?? self::CONNECT_TIMEOUT_DEFAULT;
        $this->connectTimeout = $timeout ?? self::CONNECT_TIMEOUT_DEFAULT;
        $this->serializer     = new PhpSerializer();
    }

    /**
     * {@inheritDoc}
     */
    public function get(): iterable
    {
        if ($job = $this->getConnection()->watch($this->tube)->reserveWithTimeout($this->reserveTimeout)) {
            $envelope = $this->serializer->decode([
                'body' => $job->getData(),
            ]);

            $envelope = $envelope->with(new JobStamp($job));

            return [$envelope];
        }

        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function ack(Envelope $envelope): void
    {
        /** @var JobStamp|null $jobStamp */
        $jobStamp = $envelope->last(JobStamp::class);

        if (!$jobStamp) {
            throw new LogicException('The message could not be acknowledged because it does not have job set.');
        }

        $this->getConnection()->delete($jobStamp->getJob());
    }

    /**
     * {@inheritDoc}
     */
    public function reject(Envelope $envelope): void
    {
        $this->ack($envelope);
    }

    /**
     * {@inheritDoc}
     */
    public function send(Envelope $envelope): Envelope
    {
        $uuid = uniqid('', true);

        $envelope->with(new TransportMessageIdStamp($uuid));

        $encodedMessage = $this->serializer->encode($envelope);

        /** @var DelayStamp|null $delayStamp */
        $delayStamp = $envelope->last(DelayStamp::class);
        $delay      = $delayStamp ? $delayStamp->getDelay() : PheanstalkInterface::DEFAULT_DELAY;

        /** @var TimeToRunStamp|null $timeToRunStamp */
        $timeToRunStamp = $envelope->last(TimeToRunStamp::class);
        $timeToRun      = $timeToRunStamp ? $timeToRunStamp->getTtl() : PheanstalkInterface::DEFAULT_TTR;

        $this->getConnection()->useTube($this->tube)->put(
            $encodedMessage['body'],
            PheanstalkInterface::DEFAULT_PRIORITY,
            $delay,
            $timeToRun
        );

        return $envelope;
    }

    /**
     * @return Pheanstalk
     */
    private function getConnection(): Pheanstalk
    {
        if (null === $this->connection) {
            $this->connection = Pheanstalk::create(
                $this->host,
                $this->port,
                $this->connectTimeout
            );
        }

        return $this->connection;
    }
}