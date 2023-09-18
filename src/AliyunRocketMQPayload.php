<?php

namespace Trappistes\AliyunRocketMQ;

interface AliyunRocketMQPayload
{
    /**
     * Get the plain payload of the job.
     *
     * @return string
     */
    public function getPayload(): string;
}
