<?php

namespace Trappistes\AliyunRocketMQ\Connectors;

use Illuminate\Queue\Connectors\ConnectorInterface;
use MQ\MQClient;
use ReflectionException;
use Trappistes\AliyunRocketMQ\RocketMQQueue;

class RocketMQConnector implements ConnectorInterface
{
    /**
     * @throws ReflectionException
     */
    public function connect(array $config): RocketMQQueue
    {
        $client = new MQClient(
            $config['endpoint'],
            $config['access_id'],
            $config['access_key']
        );

        return new RocketMQQueue($client, $config);
    }
}
