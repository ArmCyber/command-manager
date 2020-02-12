<?php

namespace Zakhayko\CommandManager\Commands;

use Illuminate\Console\Command;
use Zakhayko\CommandManager\ServiceContainer;

class CommandsRun extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commands:run {group?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $class = config('command-manager.manager_class');
        if (!$class || !class_exists($class)) {
            $this->error('Manager class does not exists!');
            return;
        }
        $params = [];
        new ($class())->run($params, $this);
    }
}
