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

    private $done_commands = [];

    private $default_options;

    private $options;

    private $batch;

    private function line($string, $style=null){
        if (!$this->console) return;
        $this->console->line($string, $style);
    }

    private function throw_error($message) {
        $this->line($message, 'danger');
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

    protected function action($key, $action){
        $this->addCommand($key, 'action', $action);
    }

    public function run(array $options, $console = null){
        $this->console = $console;
        $this->options = $options;
        $this->default_options = config('command-manager.options');
        $this->handle();
    }

    private function run_action($action){
        try {
            $result = $action();
            if ($result && is_scalar($result)) $this->line($result);
            return true;
        } catch (\Exception $e){
            $this->line($e->getMessage(), 'danger');
            return false;
        }
    }

    private function run_command($command) {
        $process = new Process($command);
        $process->start();
        $success = true;
        foreach ($process as $type => $data) {
            if ($type === $process::ERR) $success = false;
            echo $data;
        }
        return $success;
    }
    private function filterUndone(){
        $this->commandKeys = array_diff($this->commandKeys, DoneCommand::getFromKeys($this->commandKeys));
    }

    private function handleCommands(){
        $this->batch = DoneCommand::getBatch();
        $this->register();
        $this->filterUndone();
        if (count($this->commandKeys)==0){
            $this->line("\n".'You are up to date.', 'info');
            return;
        }
        foreach($this->commandKeys as $key) {
            if (!$this->handleCommand($key)) break;
        }
        $this->insertDoneCommands();
    }

    private function handleCommand($key){
        $this->line("\n".'Running: ' . $key, 'warning');
        $command = $this->commands[$key];
        if($command['type'] === 'command') $command_status = $this->run_command($command['action']);
        elseif ($command['type'] === 'action') $command_status = $this->run_action($command['action']);
        else {
            $this->line('Unknown type.', 'danger');
            $command_status = false;
        }
        if (!$command_status) {
            $this->line('Failed: ' . $key . '.', 'danger');
            return false;
        }
        $this->line('Completed: ' . $key, 'info');
        $this->markAsDone($key);
        return true;
    }

    private function markAsDone($key){
        $this->done_commands[] = [
            'key' => $key,
            'batch' => $this->batch,
            'done_at' => (string) now(),
        ];
    }

    private function insertDoneCommands(){
        if (!count($this->done_commands)) return;
        DoneCommand::insertDoneCommands($this->done_commands);
    }

    private function handle() {
        $this->line('Starting CommandManager.', 'warning');
        $this->handleCommands();
        $this->line("\n".'Completed CommandManager.', 'info');
    }


}
