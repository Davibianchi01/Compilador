<?php
require_once("Lexico.php");
require_once("AnalisadorSintaticoAscendenteSLR.php");
require_once("AnalisadorSemantico.php");
require_once("GeradorCodigoMIPS.php");

/*Classe principal Compilador - Responsável por coordenar todas as fases do compilador:
 *  - Análise Léxica
 *  - Análise Sintática
 *  - Análise Semântica
 *  - Geração de Código MIPS
 */

class Compilador {

private Lexico $lexico;
private AnalisadorSintaticoAscendenteSLR $sintatico;
private SemanticAnalyzer $semantico;  
private GeradorCodigoMIPS $gerador;

    //Cores ANSI para terminal
    private string $verde = "\033[32m";
    private string $vermelho = "\033[31m";
    private string $amarelo = "\033[33m";
    private string $reset = "\033[0m";

    public function __construct() {
        $this->lexico = new Lexico();
        $this->sintatico = new AnalisadorSintaticoAscendenteSLR();
        $this->semantico = new SemanticAnalyzer();
    }

    public function compilar(string $codigo): void {
        echo ">>> Iniciando compilação\n";

        //Normalizar quebras de linha
        $codigo = str_replace("\r", "", $codigo);

        //ANÁLISE LÉXICA 
        echo "\n{$this->amarelo}=== Análise Léxica ==={$this->reset}\n";
        try {
            $this->lexico->scan($codigo);
        } catch (Exception $e) {
            echo "{$this->vermelho}Erro Léxico: {$e->getMessage()}{$this->reset}\n";
            return;
        }

        echo "Tokens gerados:\n";
        foreach ($this->lexico->getTokens() as $t) {
            echo $t . "\n";
        }

        //ANÁLISE SINTÁTICA
        echo "\n{$this->amarelo}=== Análise Sintática ==={$this->reset}\n";
        try {
            $ast = $this->sintatico->parser($this->lexico);
        } catch (Exception $e) {
            echo "{$this->vermelho}Erro Sintático: {$e->getMessage()}{$this->reset}\n";
            return;
        }
        echo "Análise sintática finalizada.\n";

        //ANÁLISE SEMÂNTICA
        echo "\n{$this->amarelo}=== Análise Semântica ==={$this->reset}\n";
        $this->semantico->analisar($ast);

        if ($this->semantico->hasErrors()) {
            echo "{$this->vermelho}Erros Semânticos encontrados:{$this->reset}\n";
            foreach ($this->semantico->getErrors() as $erro) {
                echo " - $erro\n";
            }
            return;
        }
        echo "{$this->verde}Semântica OK!{$this->reset}\n";

        //GERAÇÃO DE CÓDIGO MIPS
        echo "\n{$this->amarelo}=== Gerando Código MIPS ==={$this->reset}\n";

        if (!is_array($ast)) {
            echo "{$this->vermelho}Erro: AST inválida{$this->reset}\n";
            return;
        }

        $this->gerador = new GeradorCodigoMIPS($this->semantico->getSymbolTable());
        $this->gerador->gerar($ast);

        $codigoMIPS = $this->gerador->getCode();

        echo "{$this->verde}Código MIPS gerado com sucesso!{$this->reset}\n";
        echo "\n=== Código MIPS ===\n";
        echo $codigoMIPS;

        //SALVAR EM ARQUIVO PARA MARS 
        file_put_contents("saida.asm", $codigoMIPS);
        echo "\n\nArquivo 'saida.asm' criado com sucesso para abrir no MARS.\n";
        echo "\n>>> Fim da compilação\n";
    }
}

//Exemplo de uso
$codigoExemplo = "
if (a > 10) {
    b = 'oi';
}
";

$compilador = new Compilador();
$compilador->compilar($codigoExemplo);