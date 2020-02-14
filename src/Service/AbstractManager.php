<?php
namespace Zakhayko\CommandManager\Service;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Process\Process;
use Zakhayko\CommandManager\Models\DoneCommand;

abstract class AbstractManager {
    abstract protected function register();

    private $console;

    private $commands = [];

    private $commandKeys = [];

    private $default_options;

    private $options;

    private function line($string, $style=null){
        if (!$this->console) return;
        $this->console->line($string, $style);
    }

    private function throw_error($message) {
        $this->line($message, 'error');
        die;
    }

    private function addCommand($key, $type, $action){
        if (in_array($key, $this->commandKeys)) $this->throw_error('Duplicate key "'.$key.'".');
        $this->commandKeys[] = $key;
        $this->commands[$key] = [
            'type' => $type,
            'action' => $action,
        ];
    }

    private function getOption($key){
        return $this->options[$key]??($this->default_options[$key]??null);
    }

    protected function command($key, $command){
        $this->addCommand($key, 'command', $command);
    }

    public function run(array $options, $console = null){
        $this->console = $console;
        $this->options = $options;
        $this->default_options = config('command-manager.options');
        $this->handle();
    }

    private function run_command($command) {
        $process = new Process('(cd packages/command-manager && git add . && git commit -m "test work" && git push)');
        try {
            $output = $process->mustRun()->getOutput();
            echo $output;
        } catch(\Exception $e) {
            echo $e->getMessage();
        }
    }

    private function filterUndone(){
        $this->commandKeys = array_diff($this->commandKeys, DoneCommand::getFromKeys($this->commandKeys));
    }

    private function handleCommands(){
        $this->register();
        $this->filterUndone();
        foreach($this->commandKeys as $key) {
            try {
                $this->run_command($this->commands[$key]['action']);
            } catch (\Exception $e) {
                $this->line($e->getMessage());
            }
        }
    }

    private function handle() {
        $this->handleCommands();
    }


}
