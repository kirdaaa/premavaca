<?php

require_once "lang/lexer.php";
require_once "lang/parser.php";
require_once "lang/vm.php";

?>
<!DOCTYPE html>
<pre>
<?php

$source = $_POST['source'];
$lexer = new Premavaca\Lexer($source);

try {
    $lexer->scan();

    $parser = new Premavaca\Parser($lexer->get_tokens());
    $parser->parse();

    $vm = new Premavaca\VM($parser->get_bytecode());
    $vm->execute();
} catch (Exception $error) {
    echo "Error: " . $error->getMessage() . "<br>";
}

?>
</pre>
