<?php

namespace LucaSabato\LaravelQaSetup;

use Illuminate\Support\ServiceProvider;
use LucaSabato\LaravelQaSetup\Commands\SetupQaEnvironment;

class LaravelQaSetupServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SetupQaEnvironment::class,
            ]);
        }
    }
}
