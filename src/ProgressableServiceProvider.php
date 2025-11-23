<?php

namespace Verseles\Progressable;

use Illuminate\Support\ServiceProvider;

class ProgressableServiceProvider extends ServiceProvider {
    /**
     * Register services.
     */
    public function register(): void {
        $this->mergeConfigFrom(__DIR__.'/../config/progressable.php', 'progressable');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void {
        if ($this->app->runningInConsole()) {
            $this->publishesConfig();
        }
    }

    /**
     * Publish config file
     */
    protected function publishesConfig(): void {
        $this->publishes([
            __DIR__.'/../config/progressable.php' => config_path('progressable.php'),
        ], 'config');
    }
}
