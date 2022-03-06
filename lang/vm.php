<?php

namespace Premavaca;

const CALLBACKS = [
    0b000000000 => 'cmd_write',
    0b000000001 => 'cmd_write_ascii',
    0b000000010 => 'cmd_add',
    0b000000011 => 'cmd_sub',
    0b000000100 => 'cmd_mul',
    0b000000101 => 'cmd_div',
    0b000000110 => 'cmd_mod',
    0b100000001 => 'cmd_label',
    0b100000000 => 'cmd_goto',
    0b110000000 => 'cmd_goto_eq',
    0b111000000 => 'cmd_goto_neq',
    0b111111111 => 'cmd_mkvar'
];

class State
{
    public $arguments;
    public $arg_count;

    public $position;

    public function __construct($position, $arguments = null)
    {
        $this->arguments = $arguments !== null ? $arguments : [];
        $this->arg_count = count($this->arguments);

        $this->position = $position;
    }
}

class VM
{
    private $bytecode;
    private $position = 0;

    private $variables;
    private $labels;

    private $output;

    public function __construct($bytecode)
    {
        $this->bytecode = &$bytecode;

        $this->variables = [];
        $this->labels = [];

        $this->output = [];
    }

    public function execute()
    {
        $size = count($this->bytecode);

        for (; $this->position < $size;) {
            $command = $this->pop_command();

            if ($command->op === OP_CCMD)
                $this->execute_command($command, true);
        }

        $this->position = 0;

        for (; $this->position < $size;)
            $this->execute_command($this->pop_command(), false);
    }

    private function execute_command($command, $is_ctime)
    {
        switch ($command->op) {
        case OP_CCMD:
            if (!$is_ctime) {
                $this->pop_arguments();
                break;
            }
        case OP_CMD:
            $state = new State($this->position, $this->pop_arguments());

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

    private function select_argument(&$state, $index)
    {
        if ($state->arg_count <= $index)
            throw new \Exception("missing argument #" . ($index + 1));

        return $state->arguments[$index];
    }

    // If value has been prefixed by `*` then its value will be selected
    // from `$this->variables` array
    private function get_value($value)
    {
        if ($value->is_variable)
            return $this->get_variable($value->data);

        return $value->data;
    }

    private function get_variable($name)
    {
        return array_key_exists($name, $this->variables)
            ? $this->variables[$name]
            : 0;
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

    private function cmd_label(&$state)
    {
        $name = $this->get_value($this->select_argument($state, 0));
        $this->labels[$name] = $state->position;
    }

    private function cmd_goto(&$state)
    {
        $name = $this->get_value($this->select_argument($state, 0));

        if (!array_key_exists($name, $this->labels))
            throw new \Exception("undefined label `$name`");

        $this->position = $this->labels[$name] - 1;
    }

    private function cmd_goto_eq(&$state)
    {
        $left_value = $this->get_value($this->select_argument($state, 1));
        $right_value = $this->get_value($this->select_argument($state, 2));

        if ($left_value === $right_value)
            $this->cmd_goto($state);
    }

    private function cmd_goto_neq(&$state)
    {
        $left_value = $this->get_value($this->select_argument($state, 1));
        $right_value = $this->get_value($this->select_argument($state, 2));

        if ($left_value !== $right_value)
            $this->cmd_goto($state);
    }

    private function cmd_add(&$state)
    {
        $name = $this->get_value($this->select_argument($state, 0));
        $increment = $this->get_value($this->select_argument($state, 1));

        $this->variables[$name] = $this->get_variable($name) + $increment;
    }

    private function cmd_mul(&$state)
    {
        $name = $this->get_value($this->select_argument($state, 0));
        $multiplier = $this->get_value($this->select_argument($state, 1));

        $this->variables[$name] = $this->get_variable($name) * $multiplier;
    }

    private function cmd_div(&$state)
    {
        $name = $this->get_value($this->select_argument($state, 0));
        $divider = $this->get_value($this->select_argument($state, 1));

        if ($divider === 0)
            throw new \Exception("division by 0");

        $this->variables[$name] = $this->get_variable($name) / $divider;
    }

    private function cmd_sub(&$state)
    {
        $name = $this->get_value($this->select_argument($state, 0));
        $decrement = $this->get_value($this->select_argument($state, 1));

        $this->variables[$name] = $this->get_variable($name) - $decrement;
    }

    private function cmd_mod(&$state)
    {
        $name = $this->get_value($this->select_argument($state, 0));
        $modulo = $this->get_value($this->select_argument($state, 1));

        if ($modulo === 0)
            throw new \Exception("modulo by 0");

        $this->variables[$name] = $this->get_variable($name) % $modulo;
    }

    private function cmd_mkvar(&$state)
    {
        $name = $this->get_value($this->select_argument($state, 0));
        $value = $this->get_value($this->select_argument($state, 1));

        $this->variables[$name] = $value;
    }
}

?>
