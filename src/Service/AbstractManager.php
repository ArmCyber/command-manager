<?php
namespace Zakhayko\CommandManager\Service;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Process\Process;

abstract class AbstractManager {
    abstract protected function register();

    private $console;

    private $commands;

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

    private function addCommand($key, $type, $handle){
        if ($this->commands->where('key', $key)->first()) $this->throw_error('Duplicate key "'.$key.'".');
        $this->commands->push([
            'key' => $key,
            'type' => $type,
            'handle' => $handle,
        ]);
    }

    private function getOption($key){
        return $this->options[$key]??($this->default_options[$key]??null);
    }

    protected function command($key, $command){
        $this->addCommand($key, 'command', $command);
    }

    public function run(array $options, $console = null){
        $this->console = $console;
        $this->commands = collect();
        $this->options = $options;
        $this->default_options = config('command-manager.options');
        $this->register();
        $this->handle();
    }

    private function action_command($command) {
        $process = new Process('(cd packages/command-manager && git add . && git commit -m "test work" && git push)');
        try {
            $output = $process->mustRun()->getOutput();
            echo $output;
        } catch(\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function handle() {
        foreach($this->commands as $command) {
            $this->action_command($command['handle']);

            die;
        }
    }


}
