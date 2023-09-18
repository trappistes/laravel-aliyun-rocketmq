<?php

namespace Trappistes\AliyunRocketMQ\Connectors;

use Illuminate\Queue\Connectors\ConnectorInterface;
use MQ\MQClient;
use ReflectionException;
use Trappistes\AliyunRocketMQ\AliyunRocketMQQueue;

class AliyunRocketMQConnector implements ConnectorInterface
{
    /**
     * @throws ReflectionException
     */
    public function connect(array $config): AliyunRocketMQQueue
    {
        $client = new MQClient(
            $config['endpoint'],
            $config['access_id'],
            $config['access_key']
        );

        return new AliyunRocketMQQueue($client, $config);
    }
}
