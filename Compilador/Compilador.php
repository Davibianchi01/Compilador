<?php
require_once("Lexico.php");
require_once("AnalisadorSintaticoAscendenteSLR.php");
require_once("AnalisadorSemantico.php");
require_once("GeradorCodigoMIPS.php");

class Compilador {

    private Lexico $lexico;
    private AnalisadorSintaticoAscendenteSLR $sintatico;
    private AnalisadorSemantico $semantico;
    private ?GeradorCodigoMIPS $gerador = null;

    // cores ANSI
    private string $verde = "\033[32m";
    private string $vermelho = "\033[31m";
    private string $amarelo = "\033[33m";
    private string $reset = "\033[0m";

    public function __construct() {
        $this->lexico = new Lexico();
        $this->sintatico = new AnalisadorSintaticoAscendenteSLR();
        $this->semantico = new AnalisadorSemantico();
    }

    public function compilar(string $codigo): void {

        /* ===== LÉXICO ===== */
        echo "{$this->amarelo}=== Análise Léxica ==={$this->reset}\n";
        try {
            $this->lexico->scan($codigo);
        } catch (Exception $e) {
            echo "{$this->vermelho}Erro Léxico: {$e->getMessage()}{$this->reset}\n";
            return;
        }

        foreach ($this->lexico->getTokens() as $t) {
            echo $t . "\n";
        }

        /* ===== SINTÁTICO ===== */
        echo "\n{$this->amarelo}=== Análise Sintática ==={$this->reset}\n";
        try {
            $ast = $this->sintatico->parser($this->lexico);
        } catch (Exception $e) {
            echo "{$this->vermelho}Erro Sintático: {$e->getMessage()}{$this->reset}\n";
            return;
        }

        /* ===== SEMÂNTICO ===== */
        echo "\n{$this->amarelo}=== Análise Semântica ==={$this->reset}\n";
        $this->semantico->analisar($ast);

        if ($this->semantico->hasErrors()) {
            echo "{$this->vermelho}Erros Semânticos encontrados:{$this->reset}\n";
            foreach ($this->semantico->getErrors() as $erro) {
                echo " - $erro\n";
            }
            return;
        }

        echo "{$this->verde}Compilação sem erros!{$this->reset}\n";

        /* ===== GERADOR MIPS ===== */
        echo "\n{$this->amarelo}=== Gerando Código MIPS ==={$this->reset}\n";

        $this->gerador = new GeradorCodigoMIPS(
            $this->semantico->getSymbolTable()
        );

        // AST é a entrada do gerador
        $this->gerador->gerar($ast);

        $codigoMIPS = $this->gerador->getCode();

        echo "{$this->verde}Código MIPS gerado com sucesso!{$this->reset}\n";
        echo "\n=== Código MIPS ===\n";
        echo $codigoMIPS;
    }
}