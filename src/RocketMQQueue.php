<?php

// +----------------------------------------------------------------------
// | 云计量系统 [ 博海计量科技 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2021~2025 https://www.bohaicz.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed 此软件必须经广东博海计量科技有限公司授权使用
// +----------------------------------------------------------------------
// | Author: 博海计量科技 BoHaiTeam <admin@bohaicz.com>
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace Trappistes\AliyunRocketMQ;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Queue;
use Illuminate\Support\Arr;
use MQ\Model\TopicMessage;
use MQ\MQClient;
use MQ\MQConsumer;
use MQ\MQProducer;
use ReflectionException;
use ReflectionMethod;

class RocketMQQueue extends Queue implements QueueContract
{
    /**
     * @var array
     */
    protected array $config;

    /**
     * @var ReflectionMethod
     */
    private ReflectionMethod $createPayload;

    /**
     * @var MQClient
     */
    protected MQClient $client;

    /**
     * RocketMQQueue constructor.
     *
     * @param  MQClient  $client
     * @param  array  $config
     *
     * @throws ReflectionException
     */
    public function __construct(MQClient $client, array $config)
    {
        $this->client = $client;
        $this->config = $config;

        $this->createPayload = new ReflectionMethod($this, 'createPayload');
    }

    /**
     * @return bool
     */
    public function isPlain(): bool
    {
        return (bool) Arr::get($this->config, 'plain.enable');
    }

    /**
     * @return string
     */
    public function getPlainJob(): string
    {
        return Arr::get($this->config, 'plain.job');
    }

    /**
     * Get the size of the queue.
     *
     * @param  string  $queue
     * @return int
     */
    public function size($queue = null): int
    {
        return 1;
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string|object  $job
     * @param  mixed  $data
     * @param  string  $queue
     * @return void
     *
     * @throws Exception
     */
    public function push($job, $data = '', $queue = null)
    {
        if ($this->isPlain()) {
            return $this->pushRaw($job->getPayload(), $queue);
        }

        $payload = $this->createPayload->getNumberOfParameters() === 3
            ? $this->createPayload($job, $queue, $data) // version >= 5.7
            : $this->createPayload($job, $data);

        return $this->pushRaw($payload, $queue);
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  string  $queue
     * @param  array  $options
     * @return void
     *
     * @throws Exception
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $message = new TopicMessage($payload);

        if ($this->config['use_message_tag'] && $queue) {
            $message->setMessageTag($queue);
        }

        if ($delay = Arr::get($options, 'delay', 0)) {
            $message->setStartDeliverTime(time() * 1000 + $delay * 1000);
        }

        return $this->getProducer(
            $this->config['use_message_tag'] ? $this->config['queue'] : $queue
        )->publishMessage($message);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  string|object  $job
     * @param  mixed  $data
     * @param  string  $queue
     * @return TopicMessage
     *
     * @throws Exception
     */
    public function later($delay, $job, $data = '', $queue = null): TopicMessage
    {
        $delay = method_exists($this, 'getSeconds')
            ? $this->getSeconds($delay)
            : $this->secondsUntil($delay);

        if ($this->isPlain()) {
            return $this->pushRaw($job->getPayload(), $queue, ['delay' => $delay]);
        }

        $payload = $this->createPayload->getNumberOfParameters() === 3
            ? $this->createPayload($job, $queue, $data) // version >= 5.7
            : $this->createPayload($job, $data);

        return $this->pushRaw($payload, $queue, ['delay' => $delay]);
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string  $queue
     * @return Job|null
     *
     * @throws Exception
     */
    public function pop($queue = null)
    {
        try {
            $consumer = $this->config['use_message_tag']
                ? $this->getConsumer($this->config['queue'], $queue)
                : $this->getConsumer($queue);

            /** @var array $messages */
            $messages = $consumer->consumeMessage(1, $this->config['wait_seconds']);
        } catch (Exception $e) {
            if ($e instanceof \MQ\Exception\MessageNotExistException) {
                return;
            }

            throw $e;
        }

        return new RocketMQJob(
            $this->container ?: Container::getInstance(),
            $this,
            Arr::first($messages),
            $this->config['use_message_tag'] ? $this->config['queue'] : $queue,
            $this->connectionName ?? null
        );
    }

    /**
     * Get the consumer.
     *
     * @param  string  $topicName
     * @param  string  $messageTag
     * @return MQConsumer
     */
    public function getConsumer($topicName = null, $messageTag = null)
    {
        return $this->client->getConsumer(
            $this->config['instance_id'],
            $topicName ?: $this->config['queue'],
            $this->config['group_id'],
            $messageTag
        );
    }

    /**
     * Get the producer.
     *
     * @param  string  $topicName
     * @return MQProducer
     */
    public function getProducer($topicName = null)
    {
        return $this->client->getProducer(
            $this->config['instance_id'],
            $topicName ?: $this->config['queue']
        );
    }
}
