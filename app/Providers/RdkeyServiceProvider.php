<?php

namespace App\Providers;

use App\Gadgets\Rdkey\BussKey\PromotionKey;
use App\Gadgets\Rdkey\Rdkey;
use Illuminate\Support\ServiceProvider;

class RdkeyServiceProvider extends ServiceProvider
{
    /**
     * 是否延时加载提供器。
     *
     * @var bool
     */
    protected $defer = true;

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
     * @property PromotionKey $promotion
     *
     * @return Rdkey
     */
    public function register()
    {
        $this->app->singleton('rdkey', function($app){
            return new Rdkey();
        });
    }

    /**
     * @return array
     */
    public function provides()
    {
        return ['rdkey'];
    }
}
