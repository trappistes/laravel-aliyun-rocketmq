<?php

namespace Trappistes\AliyunRocketMQ;

interface RocketMQPayload
{
    /**
     * Get the plain payload of the job.
     *
     * @return string
     */
    public function getPayload(): string;
}
