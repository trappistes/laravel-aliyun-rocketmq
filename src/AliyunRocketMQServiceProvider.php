<?php

namespace Trappistes\AliyunRocketMQ;

use Illuminate\Support\ServiceProvider;
use Trappistes\AliyunRocketMQ\Connectors\AliyunRocketMQConnector;

class AliyunRocketMQServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/rocketmq.php', 'queue.connections.rocketmq');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $queue_manager = $this->app['queue'];

        $queue_manager->addConnector('rocketmq', function () {
            return new AliyunRocketMQConnector();
        });
    }
}
