<?php

namespace Zakhayko\CommandManager;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Zakhayko\CommandManager\Commands\CommandsRun;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->loadConfig();
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()){
            $this->renderPublishes();
            $this->loadCommands();
            $this->loadMigrations();
        }
    }

    private function loadConfig(){
        $this->mergeConfigFrom(__DIR__.'/config.php', 'command-manager');
    }

    private function loadCommands(){
        $this->commands([
            CommandsRun::class,
        ]);
    }

    private function renderPublishes(){
        $this->publishes([
            __DIR__.'/config.php' => config_path().'/command-manager.php',
            __DIR__.'/manager.stub' => app_path().'/console/CommandManager.php',
        ], 'command-manager');
    }

    private function loadMigrations(){
        $this->loadMigrationsFrom(__DIR__.'/migrations');
    }
}
