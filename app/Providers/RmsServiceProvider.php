<?php


namespace App\Providers;



use App\Gadgets\Rms\Rms;
use Illuminate\Support\ServiceProvider;

class RmsServiceProvider extends ServiceProvider
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
     *
     * @return Rms
     */
    public function register()
    {
        $this->app->singleton('rms', function($app){
            return new Rms();
        });
    }

    /**
     * @return array
     */
    public function provides()
    {
        return ['rms'];
    }
}
