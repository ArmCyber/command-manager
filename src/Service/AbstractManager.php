<?php
namespace Zakhayko\CommandManager\Service;

use Symfony\Component\Process\Process;
use Zakhayko\CommandManager\Models\DoneCommand;

abstract class AbstractManager {
    abstract protected function register();

    private $console;

    private $commands = [];

    private $commandKeys = [];

    private $done_commands = [];

    private $default_options = [
        'test_mode' => false,
    ];

    private $options;

    private $batch;

    private $handling = false;

    protected function line($string, $style=null){
        if (!$this->console) return;
        $this->console->line($string, $style);
    }

    protected function info($string){
        $this->line($string, 'info');
    }

    protected function warn($string){
        $this->line($string, 'warning');
    }

    protected function error($string) {
        $this->line($string, 'danger');
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

    protected function command($key, $command=null){
        if (!$this->handling) {
            if (!$command) $this->throw_error('No command provided.');
            $this->addCommand($key, 'command', $command);
        }
        else {
            $this->warn('Running action command: '.$key);
            $this->run_command($key);
            $this->info('Completed action command: '.$key);
        }
        return true;
    }

    protected function action($key, $action){
        $this->addCommand($key, 'action', $action);
    }

    public function run(array $options, $console = null){
        $this->console = $console;
        $this->options = $options;
        $this->handle();
    }

    private function run_action($action){
        try {
            $result = $action();
            if ($result && is_scalar($result)) $this->line($result);
            return true;
        } catch (\Exception $e){
            $this->error($e->getMessage());
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
            $this->info("\n".'You are up to date.');
            return;
        }
        $this->handling = true;
        foreach($this->commandKeys as $key) {
            if (!$this->handleCommand($key)) break;
        }
        $this->insertDoneCommands();
    }

    private function handleCommand($key){
        $this->warn("\n".'Running: ' . $key);
        $command = $this->commands[$key];
        if($command['type'] === 'command') $command_status = $this->run_command($command['action']);
        elseif ($command['type'] === 'action') $command_status = $this->run_action($command['action']);
        else {
            $this->error('Unknown type.');
            $command_status = false;
        }
        if (!$command_status) {
            $this->error('Failed: ' . $key . '.');
            return false;
        }
        $this->info('Completed: ' . $key);
        if (!$this->getOption('test_mode')) $this->markAsDone($key);
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
        $this->warn('Starting CommandManager.');
        $this->handleCommands();
        $this->info("\n".'Completed CommandManager.');
    }


}
