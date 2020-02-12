<?php
namespace Zakhayko\CommandManager\Service;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;

abstract class AbstractManager {
    abstract protected function register();

    private $console;

    private $commands;

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

    protected function command($key, $command){
        $this->addCommand($key, 'command', $command);
    }

    public function run(array $options, $console = null){
        $this->console = $console;
        $this->commands = collect();
        $this->options = $options;
        $this->register();
        $this->handle();
    }


}
