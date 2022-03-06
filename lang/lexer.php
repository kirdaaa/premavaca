<?php

namespace Premavaca;

const BIT_STRING = 'premavaca';

const TK_ANY = 0;
const TK_BLANK = 1;
const TK_BIT = 2;
const TK_VALUE = 3;
const TK_CMDBEGIN = 4;
const TK_CMDEND = 5;
const TK_VAR = 6;
const TK_CTIME = 7;

const SM_CMDBEGIN = '[';
const SM_CMDEND = ']';
const SM_VAR = '*';
const SM_COMMENT = '?';
const SM_CTIME = '@';

class Token
{
    public $type;
    public $data;

    public function __construct($type, $data = null)
    {
        $this->type = $type;
        $this->data = $data;
    }
}

class Lexer
{
    public $source;

    private $tokens;
    private $token;

    private $bit_offset = 0;
    private $bit_sequence = [];

    private $is_comment = false;

    public function __construct($source)
    {
        // Space is added because scanner can only detect previous characters
        // and not following ones which caused bug where last character
        // would be ignored
        $this->source = $source . " ";
        $this->token = new Token(TK_ANY);

        $this->tokens = [];
    }

    public function get_tokens()
    {
        return $this->tokens;
    }

    public function scan()
    {
        $symbols = str_split($this->source);

        foreach ($symbols as $position => $symbol)
            $this->scan_symbol($symbol, $position);
    }

    // Writes out tokens in human readable form into HTML
    public function dump()
    {
        echo "<div style=\"font-family: monospace;\">";

        foreach ($this->tokens as $index => $token) {
            echo "<div>";
            echo "<span style=\"display: inline-block;
                                width: 48px;\">$index: </span>";
            echo "<span>" . token_tostring($token) . "</span>";
            echo "</div>";
        }

        echo "</div>";
    }

    // Merges bit sequence into number and returns it while clearing the
    // bit sequence array
    private function process_sequence()
    {
        $count = count($this->bit_sequence);
        $value = 0;

        foreach ($this->bit_sequence as $index => $bit) {
            $value += $bit;
            $value <<= 1;

            $this->bit_sequence[$index] = null;
        }

        return $value >> 1;
    }

    private function scan_symbol($symbol, $position)
    {
        // Ignore symbols if they are commented
        if ($this->is_comment) {
            if ($symbol === "\n")
                $this->is_comment = false;
            else
                $symbol = ' '; 
        }

        $previous_type = $this->token->type;
        $push_token = null;

        switch ($symbol) {
        case ' ':
        case "\t":
        case "\r":
        case "\n":
            $this->set_token(TK_BLANK, false);
            break;
        case SM_CMDBEGIN:
            $this->set_token(TK_CMDBEGIN, true);
            break;
        case SM_CMDEND:
            $push_token = TK_CMDEND;
            break;
        case SM_VAR:
            $push_token = TK_VAR;
            break;
        case SM_COMMENT:
            $this->is_comment = true;
            break;
        case SM_CTIME:
            $push_token = TK_CTIME;
            break;
        default:
            $this->scan_default($symbol, $position);
        }

        if (
            $previous_type === TK_BIT
            && ($this->token->type !== TK_BIT || $push_token !== null)
        ) {
            if ($this->bit_offset !== 0)
                throw new \Exception("($position): unclosed bit sequence");

            $this->set_token(TK_VALUE, true, $this->process_sequence());
        }

        if ($push_token !== null)
            $this->set_token($push_token, true);
    }

    private function scan_default($symbol, $position)
    {
        $value = get_bit_value($symbol, $this->bit_offset);

        if ($value === null)
            throw new \Exception("($position): unexpected `$symbol`");

        $this->bit_offset = ($this->bit_offset + 1) % strlen(BIT_STRING);
        $this->bit_sequence[] = $value;

        $this->set_token(TK_BIT, false, $value);
    }

    private function set_token($type, $push, $data = null)
    {
        $token = new Token($type, $data);

        if ($push)
            $this->tokens[] = $token;

        $this->token = $token;
    }
}

function get_bit_value($symbol, $offset)
{
    $expected_bit = BIT_STRING[$offset];

    if ($symbol === $expected_bit)
        return 0;
    elseif ($symbol === strtoupper($expected_bit))
        return 1;

    return null;
}

function token_tostring($token)
{
    $type = $token->type;

    if ($type === TK_VALUE) return "$token->data";
    if ($type === TK_BIT) return "BIT($token->data)";
    if ($type === TK_CMDBEGIN) return SM_CMDBEGIN;
    if ($type === TK_CMDEND) return SM_CMDEND;
    if ($type === TK_VAR) return SM_VAR;
    if ($type === TK_CTIME) return SM_CTIME;

    return "{any}";
}

function token_type_symbol($type)
{
    if ($type === TK_VALUE) return "value";
    if ($type === TK_CMDBEGIN) return "`" . SM_CMDBEGIN . "`";
    if ($type === TK_CMDEND) return "`" . SM_CMDEND . "`";
    if ($type === TK_VAR) return "`" . SM_VAR . "`";
    if ($type === TK_CTIME) return "`" . SM_CTIME . "`";

    return "{unknown}";
}

?>
