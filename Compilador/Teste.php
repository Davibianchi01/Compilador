<?php
require_once("Lexico.php");

$codigo = "
if (a > 10) {
    b = 'oi';
}
";

// Cria o léxico
$lexico = new Lexico();

// Analisa o código
$lexico->scan($codigo);

// Pega todos os tokens
$tokens = $lexico->getTokens();

// Imprime cada token
foreach ($tokens as $t) {
    echo $t . "\n";
}
