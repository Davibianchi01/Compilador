<?php
//Classe de testes do compilador completo
//Fluxo: Léxico → Sintático → Semântico → Código MIPS

require_once "Lexico.php";
require_once "AnalisadorSintaticoAscendenteSLR.php";
require_once "AnalisadorSemantico.php";
require_once "GeradorCodigoMIPS.php";

$codigo = "
int a = 11;
string b;

if (a > 10) {
    b = "oi";
}
";

echo ">>> INICIANDO TESTE DO COMPILADOR <<<\n";

try {
    //ANÁLISE LÉXICA 
    echo "=== ANÁLISE LÉXICA ===\n";
    $lexico = new Lexico();
    $lexico->scan($codigo);

    foreach ($lexico->getTokens() as $token) {
        echo $token . PHP_EOL;
    }

    //ANÁLISE SINTÁTICA
    echo "\n=== ANÁLISE SINTÁTICA ===\n";
    $parser = new Sintatico($lexico->getTokens());
    $ast = $parser->parse();
    echo "✔ AST gerada com sucesso\n";

    //ANÁLISE SEMÂNTICA 
    echo "\n=== ANÁLISE SEMÂNTICA ===\n";
    $semantico = new AnalisadorSemantico();
    $tabelaSimbolos = $semantico->analisar($ast);

    if ($semantico->hasErrors()) {
        echo "ERROS SEMÂNTICOS:\n";
        foreach ($semantico->getErrors() as $erro) {
            echo " - $erro\n";
        }
        exit;
    }

    echo "Análise semântica OK\n";

    //GERAÇÃO DE CÓDIGO MIPS
    echo "\n=== GERAÇÃO DE CÓDIGO MIPS ===\n";
    $gerador = new GeradorCodigoMIPS($tabelaSimbolos);
    $gerador->gerar($ast);

    $codigoMIPS = $gerador->getCode();

    echo "\n===== CÓDIGO MIPS GERADO =====\n";
    echo $codigoMIPS;

    //SALVAR EM ARQUIVO
    file_put_contents("saida.asm", $codigoMIPS);
    echo "\n✔ Arquivo 'saida.asm' criado com sucesso\n";

    echo "\n>>> TESTE FINALIZADO COM SUCESSO <<<\n";

} catch (Throwable $e) {
    echo "\nERRO DURANTE A COMPILAÇÃO\n";
    echo $e->getMessage() . "\n";
}