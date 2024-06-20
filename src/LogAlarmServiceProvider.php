<?php

namespace Saasscaleup\LogAlarm;

use Illuminate\Support\ServiceProvider;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Log;

class LogAlarmServiceProvider extends ServiceProvider{


    public function boot()
    {
        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            // Publish the configuration file.
            $this->publishes([
                __DIR__ . '/config/log-alarm.php' => config_path('log-alarm.php'),
            ]);
        }


        if (config('log-alarm.enabled')){
            Log::listen(function (MessageLogged $event) {
                app(LogHandler::class)->handle($event);
            });
        }
    }

    public function register(){
        
    }
}