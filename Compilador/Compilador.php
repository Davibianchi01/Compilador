<?php
require_once("Lexico.php");
require_once("AnalisadorSintaticoAscendenteSLR.php");
require_once("AnalisadorSemantico.php");
require_once("GeradorCodigoMIPS.php");

class Compilador {

    private Lexico $lexico;
    private AnalisadorSemantico $semantico;
    private AnalisadorSintaticoAscendenteSLR $sintatico;
    private ?GeradorCodigoMIPS $gerador;

    // Cores ANSI
    private string $verde = "\033[32m";
    private string $vermelho = "\033[31m";
    private string $amarelo = "\033[33m";
    private string $reset = "\033[0m";

    public function __construct() {
        $this->lexico = new Lexico();
        $this->semantico = new AnalisadorSemantico();
        $this->sintatico = new AnalisadorSintaticoAscendenteSLR();
        $this->gerador = null;
    }

    public function compilar(string $codigo, string $arquivoSaidaMIPS = "saida.asm") {

        echo "{$this->amarelo}=== Análise Léxica ==={$this->reset}\n";
        try {
            $this->lexico->scan($codigo);   
        } catch (Exception $e) {
            echo "{$this->vermelho}Erro Léxico: {$e->getMessage()}{$this->reset}\n";
            return;
        }

        $tokens = $this->lexico->getTokens();
        echo "Tokens encontrados:\n";
        foreach ($tokens as $token) {
            echo $token->__toString() . "\n";
        }

        echo "\n{$this->amarelo}=== Análise Sintática e Semântica ==={$this->reset}\n";
        try {
            $resultado = $this->sintatico->parser($this->lexico, true); // debug = true para mostrar shifts/reduces
        } catch (Exception $e) {
            echo "{$this->vermelho}Erro Sintático: {$e->getMessage()}{$this->reset}\n";
            return;
        }

        if ($this->semantico->hasErrors()) {
            echo "\n{$this->vermelho}Erros Semânticos encontrados:{$this->reset}\n";
            foreach ($this->semantico->getErrors() as $erro) {
                echo $erro . "\n";
            }
            return; 
        }
        
        echo "{$this->verde}Compilação sem erros!{$this->reset}\n";

        echo "\n{$this->amarelo}=== Tabela de Símbolos ==={$this->reset}\n";
        $tabela = $this->semantico->getSymbolTable();
        if (empty($tabela)) {
            echo "{$this->amarelo}⚠ AVISO: A tabela de símbolos está vazia!{$this->reset}\n";
        } else {
            print_r($tabela);
        }

        // Geração de código MIPS
        echo "\n{$this->amarelo}=== Gerando Código MIPS ==={$this->reset}\n";
        $this->gerador = new GeradorCodigoMIPS($tabela);

        foreach ($tabela as $nome => $simbolo) {
            if (isset($simbolo->meta['valor'])) {
                $this->gerador->gerarAtribuicao($nome, $simbolo->meta['valor']);
            }
        }

        $this->gerador->finalizar();
        $this->gerador->salvar($arquivoSaidaMIPS);

        echo "{$this->verde}Código MIPS gerado e salvo em '{$arquivoSaidaMIPS}'{$this->reset}.\n";
    }

    public function getCodigoMIPS(): ?string {
        return $this->gerador?->getCodigo();
    }
}

// Exemplo de uso
$codigo = <<<COD
int x;
x = 10;
COD;

$compilador = new Compilador();
$compilador->compilar($codigo);