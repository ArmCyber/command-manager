<?php

namespace Zakhayko\CommandManager\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

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
     * @return void
     */
    public function handle()
    {
        $class = config('command-manager.manager_class');
        if (!$class || !class_exists($class)) {
            $this->error('Manager class does not exists!');
            return;
        }
        $options = [];
        (new $class)->run($options, $this);
        return;
    }

    public function line($string, $style = null, $verbosity = null)
    {
        if ($style === 'warning' && !$this->output->getFormatter()->hasStyle('warning')) {
            $newStyle = new OutputFormatterStyle('yellow');
            $this->output->getFormatter()->setStyle('warning', $newStyle);
        }
        parent::line($string, $style, $verbosity);
    }
}
