<?php

namespace Trappistes\AliyunRocketMQ;

use Illuminate\Support\ServiceProvider;

class RocketMQServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/rocketmq.php', 'queue.connections.rocketmq');
        $this->publishes([
            __DIR__.'/Jobs/RocketMQPlainJob.php' => base_path('app/Jobs/RocketMQPlainJob.php'),
        ]);
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
            return new RocketMQConnector();
        });
    }
}
