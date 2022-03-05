<?php

namespace Premavaca;

const CALLBACKS = [
    0 => 'cmd_write',
    1 => 'cmd_write_ascii'
];

class State
{
    public $arguments;
    public $arg_count;

    public function __construct($arguments = null)
    {
        $this->arguments = $arguments !== null ? $arguments : [];
        $this->arg_count = count($this->arguments);
    }
}

class VM
{
    private $bytecode;
    private $position = 0;

    private $variables;
    private $output;

    public function __construct($bytecode)
    {
        $this->bytecode = &$bytecode;

        $this->variables = [];
        $this->output = [];
    }

    public function execute()
    {
        $size = count($this->bytecode);

        for (; $this->position < $size;)
            $this->execute_command($this->pop_command());
    }

    private function execute_command($command)
    {
        switch ($command->op) {
        case OP_CMD:
            $state = new State($this->pop_arguments());
            $id = $this->get_callback_id($command);

            if ($id === null)
                throw new \Exception("unknown callback");

            $callback = CALLBACKS[$id];
            $this->$callback($state);

            break;
        case OP_ARG:
            throw new \Exception("program must start with a command");
        }
    }

    private function get_callback_id($command)
    {
        if (!array_key_exists(0, $command->arguments))
            return null;

        $id = $command->arguments[0]->data;

        if (!array_key_exists($id, CALLBACKS))
            return null;

        return $id;
    }

    private function pop_arguments()
    {
        if (!array_key_exists($this->position, $this->bytecode))
            return null;

        $command = $this->bytecode[$this->position];

        if ($command->op !== OP_ARG)
            return null;

        $this->position++;

        return $command->arguments;
    }

    private function pop_command()
    {
        if (!array_key_exists($this->position, $this->bytecode))
            return null;

        return $this->bytecode[$this->position++];
    }

    private function get_value($value)
    {
        return $value->is_variable ? $value->data : $value->data;
    }

    private function cmd_write(&$state)
    {
        foreach ($state->arguments as $value) {
            echo $this->get_value($value);
        }
    }

    private function cmd_write_ascii(&$state)
    {
        foreach ($state->arguments as $value) {
            echo chr($this->get_value($value));
        }
    }
}

?>
