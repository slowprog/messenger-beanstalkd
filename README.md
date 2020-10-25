# Messenger Beanstalkd Transport

Beanstalkd transport for Symfony's Messenger component.

## Installation

The component requires PHP 7.3+ and Symfony 4.3+. You can install this component using composer:

```
composer require slowprog/messenger-beanstalkd
```

## Basic usage

Set environment variable:

```
MESSENGER_TRANSPORT_DSN=beanstalkd://%beanstalkd_host%:%beanstalkd_port%
```

Set messenger transport config:

```yaml
framework:
    messenger:
        transports:
            beanstalkd_queues:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                options:
                    queue_name: '%your_tube_name%'
                    reserve_timeout: '%reserve_timeout_in_seconds%'
                    connect_timeout: '%connect_timeout_in_seconds%'
```

Default options:

* queue_name - default
* reserve_timeout - 1
* connect_timeout - 10

## Further reading

* [The Messenger Component](https://symfony.com/doc/current/components/messenger.html)
* [Messenger: Sync & Queued Message Handling](https://symfony.com/doc/current/messenger.html)
* [Beanstalkd](https://beanstalkd.github.io/)
