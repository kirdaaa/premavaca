<?php

namespace Premavaca;

const OP_CMD = 0;
const OP_ARG = 1;
const OP_CCMD = 2;

class Command
{
    public $op;
    public $arguments;

    public function __construct($op, $arguments)
    {
        $this->op = $op;
        $this->arguments = $arguments;
    }
}

class Value
{
    public $data;
    public $is_variable;

    public function __construct($data, $is_variable)
    {
        $this->data = $data;
        $this->is_variable = $is_variable;
    }
}

class Parser
{
    private $bytecode;

    private $tokens;
    private $position = 0;

    private $arguments;

    private $in_block = false;
    private $expect_variable = false;
    private $push_compile_time = false;

    private $allowed_tokens;

    public function __construct($tokens)
    {
        $this->tokens = &$tokens;
        $this->bytecode = [];

        $this->arguments = [];

        $this->allowed_tokens = [TK_CMDBEGIN, TK_CTIME];
    }

    public function get_bytecode()
    {
        return $this->bytecode;
    }

    public function parse()
    {
        $size = count($this->tokens);

        for (; $this->position < $size;)
            $this->parse_token($this->pop_token());

        $this->push_optional_arguments();
    }

    private function parse_token($token)
    {
        $this->check_valid_token($token);

        switch ($token->type) {
        case TK_CMDBEGIN:
            $this->push_optional_arguments();

            $this->in_block = true;
            $this->allowed_tokens = [TK_VALUE, TK_VAR];

            break;
        case TK_CMDEND:
            if ($this->push_compile_time) {
                $this->push_command(OP_CCMD);
                $this->push_compile_time = false;
            }
            else
                $this->push_command(OP_CMD);

            $this->in_block = false;

            $this->allowed_tokens = [TK_CMDBEGIN, TK_CTIME, TK_VALUE, TK_VAR];

            break;
        case TK_VALUE:
            $this->arguments[] = new Value(
                $token->data,
                $this->expect_variable
            );

            $this->expect_variable = false;

            $this->allowed_tokens = $this->in_block
                ? [TK_CMDEND]
                : [TK_VAR, TK_VALUE, TK_CMDEND, TK_CMDBEGIN, TK_CTIME];

            break;
        case TK_VAR:
            $this->expect_variable = true;
            $this->allowed_tokens = [TK_VALUE];

            break;
        case TK_CTIME:
            $this->push_compile_time = true;
            $this->allowed_tokens = [TK_CMDBEGIN];
        }
    }

    private function push_optional_arguments()
    {
        if (count($this->arguments) > 0)
            $this->push_command(OP_ARG);
    }

    private function pop_token($expected_type = null)
    {
        $token = $this->tokens[$this->position++];

        if ($expected_type === null || $token->type === $expected_type)
            return $token;

        $this->invalid_token($token->type, [$expected_type]);
    }

    private function check_valid_token($token)
    {
        if ($token->type === TK_ANY)
            return;

        if (in_array($token->type, $this->allowed_tokens))
            return;

        $this->invalid_token($token->type, $this->allowed_tokens);
    }

    private function invalid_token($type, $expected_types)
    {
        $expected = array_map(function($value) {
            return token_type_symbol($value);
        }, $expected_types);

        throw new \Exception(
            "expected " . implode(' or ', $expected)
            . ", got " . token_type_symbol($type)
        );
    }

    private function push_command($op)
    {
        $this->bytecode[] = new Command($op, $this->arguments);
        $this->arguments = [];
    }
}

?>
