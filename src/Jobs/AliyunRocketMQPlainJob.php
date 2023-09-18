<?php

namespace Trappistes\AliyunRocketMQ\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Trappistes\AliyunRocketMQ\AliyunRocketMQPayload;

class AliyunRocketMQPlainJob implements AliyunRocketMQPayload, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $payload;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    /**
     * Get the plain payload of the job.
     *
     * @return string
     */
    public function getPayload(): string
    {
        return $this->payload;
    }

    public function handle()
    {
        // TODO
    }
}
