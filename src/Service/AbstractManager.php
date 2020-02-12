<?php
namespace Zakhayko\CommandManager\Service\AbstractManager;

abstract class AbstractManager {
    abstract protected function register();

    private $console;

    private $commands = [];

    private function line($string, $style=null){
        if (!$this->console) return;
        $this->console->line($string, $style);
    }

    protected function command($key, $command){
        $this->commands[] = [
            'key' => $key,
            'type' => 'command',
            'handle' => $command,
        ];
    }

    public function run($params, $console = null){
        $this->console = $console;

    }
}
