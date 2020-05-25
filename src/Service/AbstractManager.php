<?php

namespace Zakhayko\CommandManager\Service;

use Symfony\Component\Process\Process;
use Zakhayko\CommandManager\Models\DoneCommand;

abstract class AbstractManager
{

    abstract protected function register();

    private $console;

    private $commands = [];

    private $commandKeys = [];

    private $done_commands = [];

    private $options;

    private $default_options;

    private $batch;

    private $handling = false;

    public function __construct()
    {
        $this->default_options = $this->getDefaultOptions();
    }

    protected function getDefaultOptions()
    {
        return [
            'test_mode' => false,
            'group' => $this->config('default_group', 'self'),
        ];
    }

    protected function line($string, $style = null)
    {
        if (!$this->console) return;
        $this->console->line($string, $style);
    }

    protected function info($string)
    {
        $this->line($string, 'info');
    }

    protected function warn($string)
    {
        $this->line($string, 'warning');
    }

    protected function error($string)
    {
        $this->line($string, 'danger');
    }

    private function throw_error($message)
    {
        $this->error($message);
        die;
    }

    private function addCommand($key, $type, $action)
    {
        if (in_array($key, $this->commandKeys)) $this->throw_error('Duplicate key "' . $key . '".');
        $this->commandKeys[] = $key;
        $this->commands[$key] = [
            'type' => $type,
            'action' => $action,
        ];
    }

    private function config($key, $default = null)
    {
        return config('command-manager.' . $key, $default);
    }

    private function option($key)
    {
        return $this->options[$key] ?? ($this->default_options[$key] ?? null);
    }

    protected function command($key, $command = null)
    {
        if (!$this->handling) {
            if (!$command) $this->throw_error('No command provided.');
            $this->addCommand($key, 'command', $command);
            return true;
        }
        $this->warn('Running action command: ' . $key);
        $status = $this->run_command($key);
        $this->info('Completed action command: ' . $key);
        return $status;
    }

    protected function action($key, $action)
    {
        $this->addCommand($key, 'action', $action);
    }

    public function run(array $options, $console = null)
    {
        $this->console = $console;
        $this->options = $options;
        $this->handle();
    }

    private function run_action($action)
    {
        try {
            $result = $action();
            if ($result && is_scalar($result)) $this->line($result);
            return true;
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return false;
        }
    }

    private function run_command($command)
    {
        $process = Process::fromShellCommandline($command);
        $process->start();
        $success = true;
        foreach ($process as $type => $data) {
            if ($type === $process::ERR) $success = false;
            echo $data;
        }
        if (!$success && $this->isCommandErrorWhitelisted($command)) $success = true;
        return $success;
    }

    private function isCommandErrorWhitelisted($command)
    {
        $errorWhitelistedCommands = $this->config('skip_errors', []);
        if (!empty($errorWhitelistedCommands) && is_array($errorWhitelistedCommands)) {
            $exploded = explode(' ', mb_strtolower($command));
            foreach ($errorWhitelistedCommands as $whitelistedCommand) {
                $whitelistedCommandExploded = explode(' ', mb_strtolower($whitelistedCommand));
                if (array_splice($exploded, 0, count($whitelistedCommandExploded)) == $whitelistedCommandExploded) return true;
            }
        }

        return false;
    }

    private function filterUndone()
    {
        $this->commandKeys = array_diff($this->commandKeys, DoneCommand::getFromKeys($this->commandKeys));
    }

    private function handleGroup($group)
    {
        foreach ($group as $command) {
            if ($command == 'self') {
                $this->handleCommands();
                continue;
            }
            $this->warn("\n" . 'Running group command: ' . $command);
            if (!$this->run_command($command)) $this->throw_error('Group command "' . $command . '" failed.');
            $this->info('Completed group command: ' . $command);
        }
    }

    private function handleCommands()
    {
        $this->warn("\n" . 'Starting CommandManager commands.');
        $this->batch = DoneCommand::getBatch();
        $this->register();
        $this->filterUndone();
        if (count($this->commandKeys) == 0) {
            $this->info('You are up to date.');
        } else {
            $this->handling = true;
            foreach ($this->commandKeys as $key) {
                if (!$this->handleCommand($key)) {
                    $die_after = true;
                    break;
                }
            }
            $this->insertDoneCommands();
            if (isset($die_after)) die;
        }
        $this->info('Completed CommandManager.');
    }

    private function handleCommand($key)
    {
        $this->warn('Running: ' . $key);
        $command = $this->commands[$key];
        if ($command['type'] === 'command') $command_status = $this->run_command($command['action']);
        elseif ($command['type'] === 'action') $command_status = $this->run_action($command['action']);
        else {
            $this->error('Unknown type "' . $command['type'] . '".');
            $command_status = false;
        }
        if (!$command_status) {
            $this->error('Failed: ' . $key . '.');
            return false;
        }
        $this->info('Completed: ' . $key);
        if (!$this->option('test_mode')) $this->markAsDone($key);
        return true;
    }

    private function markAsDone($key)
    {
        $this->done_commands[] = [
            'key' => $key,
            'batch' => $this->batch,
            'done_at' => (string)now(),
        ];
    }

    private function insertDoneCommands()
    {
        if (!count($this->done_commands)) return;
        DoneCommand::insertDoneCommands($this->done_commands);
    }

    private function handle()
    {
        $groupName = $this->option('group');
        if ($groupName == 'self') $group = ['self'];
        else {
            $group = $this->config('groups')[$groupName] ?? null;
            if (!$group) $this->throw_error('Group "' . $groupName . '" is undefined.');
        }
        $this->info(($this->option('test_mode') ? '(Test mode) ' : '') . 'Using group "' . $groupName . '"');
        $this->handleGroup($group);
    }


}
