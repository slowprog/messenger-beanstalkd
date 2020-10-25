<?php

namespace SlowProg\Beanstalkd\Messenger\Transport;

use LogicException;
use Pheanstalk\Pheanstalk;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class BeanstalkTransportFactory implements TransportFactoryInterface
{
    /**
     * @var string
     */
    private const BEANSTALKD_PROTO = 'beanstalkd';

    /**
     * beanstalkd: - connects to localhost:11300
     * beanstalkd://host:port
     *
     * @param string              $dsn
     * @param array               $options
     * @param SerializerInterface $serializer
     *
     * @return TransportInterface
     */
    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        $config = $this->parseDsn($dsn);

        return new BeanstalkTransport(
            $config['host'] ?? null, 
            $config['port'] ?? null, 
            $options['queue_name'] ?? null,
            $options['reserve_timeout'] ?? null, 
            $options['connect_timeout'] ?? null
        );
    }

    /**
     * @param string $dsn
     * @param array  $options
     *
     * @return bool
     */
    public function supports(string $dsn, array $options): bool
    {
        return 0 === strpos($dsn, self::BEANSTALKD_PROTO . '://');
    }

    /**
     * @param string $dsn
     *
     * @return array
     */
    private function parseDsn(string $dsn): array
    {
        $dsnConfig = parse_url($dsn);
        $query     = [];

        if (false === $dsnConfig) {
            throw new LogicException(sprintf('Failed to parse DSN "%s"', $dsn));
        }

        $dsnConfig = array_replace([
            'scheme' => null,
            'host'   => null,
            'port'   => null,
            'user'   => null,
            'pass'   => null,
            'path'   => null,
            'query'  => null,
        ], $dsnConfig);

        if (self::BEANSTALKD_PROTO !== $dsnConfig['scheme']) {
            throw new LogicException(sprintf(
                'The given DSN scheme "%s" is not supported. Could be "%s" only.',
                $dsnConfig['scheme'],
                self::BEANSTALKD_PROTO
            ));
        }

        if ($dsnConfig['query']) {
            parse_str($dsnConfig['query'], $query);
        }

        return array_replace($query, [
            'port' => $dsnConfig['port'],
            'host' => $dsnConfig['host'],
        ]);
    }
}