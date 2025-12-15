<?php
require_once "Lexico.php";
require_once "AnalisadorSintaticoAscendenteSLR.php";
require_once "AnalisadorSemantico.php";
require_once "GeradorCodigoMIPS.php";

$codigo = "
if (a > 10) {
    b = 'oi';
}
";

//Lexico

$lexico = new Lexico();
$lexico->scan($codigo);
$tokens = $lexico->getTokens();

//Sintatico
$parser = new Sintatico($tokens);
$ast = $parser->parse();

//Semantico
$semantico = new AnalisadorSemantico();
$tabelaSimbolos = $semantico->analisar($ast);

//Gerador MIPS
$gerador = new GeradorCodigoMIPS($tabelaSimbolos);
$gerador->gerar($ast);

echo $gerador->getCode();
