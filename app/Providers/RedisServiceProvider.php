<?php

namespace App\Providers;

use App\Gadgets\Redis\RedisManager;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;

class RedisServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('predis', function($app){
            $config = $app->make('config')->get('database.redis');

            return new RedisManager(
                app(),
                Arr::pull($config, 'client', 'predis'),
                $config
            );
        });

        $this->app->bind('redis.connection', function ($app) {
            return $app['redis']->connection();
        });
    }
}
