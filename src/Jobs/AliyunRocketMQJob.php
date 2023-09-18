<?php

namespace Trappistes\AliyunRocketMQ\Jobs;

use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\Job;
use MQ\Model\Message;
use Trappistes\AliyunRocketMQ\AliyunRocketMQQueue;

class AliyunRocketMQJob extends Job implements JobContract
{
    /**
     * @var AliyunRocketMQQueue
     */
    protected AliyunRocketMQQueue $connection;

    /**
     * @var Message
     */
    protected Message $message;

    /**
     * Create a new job instance.
     *
     * @param  Container  $container
     * @param  AliyunRocketMQQueue  $connection
     * @param  Message  $message
     * @param  string  $queue
     * @param  string  $connectionName
     */
    public function __construct(
        Container           $container,
        AliyunRocketMQQueue $connection,
        Message             $message,
        string              $queue,
        string              $connectionName
    ) {
        $this->container = $container;
        $this->connection = $connection;
        $this->message = $message;
        $this->queue = $queue;
        $this->connectionName = $connectionName;
    }

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId(): string
    {
        return $this->message->getMessageId();
    }

    /**
     * Get the raw body of the job.
     *
     * @return string
     */
    public function getRawBody(): string
    {
        return $this->message->getMessageBody();
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts(): int
    {
        return $this->message->getConsumedTimes();
    }

    /**
     * Fire the job.
     *
     * @return void
     */
    public function fire()
    {
        method_exists($this, 'resolveAndFire')
            ? $this->resolveAndFire($this->payload())
            : parent::fire();
    }

    /**
     * Get the decoded body of the job.
     *
     * @return array
     */
    public function payload(): array
    {
        if ($this->connection->isPlain()) {
            $job = $this->connection->getPlainJob();

            return [
                'displayName' => is_string($job) ? explode('@', $job)[0] : null,
                'job' => $job,
                'maxTries' => null,
                'timeout' => null,
                'data' => $this->getRawBody(),
            ];
        }

        return json_decode($this->getRawBody(), true);
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete()
    {
        parent::delete();

        $this->connection
            ->getConsumer($this->getQueue())
            ->ackMessage([$this->message->getReceiptHandle()]);
    }

    /**
     * Release the job back into the queue.
     *
     * @param  int  $delay
     * @return void
     */
    public function release($delay = 0)
    {
        parent::release($delay);
    }
}
