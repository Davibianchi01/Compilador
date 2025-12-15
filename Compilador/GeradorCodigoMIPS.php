<?php
require_once("AnalisadorSemantico.php");

class GeradorCodigoMIPS {
     //Responsável por gerar código assembly MIPS a partir:
     //Da tabela de símbolos (análise semântica) e da (Árvore Sintática Abstrata)

    private $tabela;          // Tabela de símbolos
    private $dataSegment;     // Segmento .data do código MIPS - Armazena variáveis e strings
    private $textSegment;     // Segmento .text do código MIPS - Contém as instruções executáveis
    private $labelCounter;    // Contador usado para gerar rótulos (labels)
    private $varOffsets;      // Controle das variáveis já declaradas
    private $stringCounter;   // Controle de strings literais

    public function __construct(array $tabelaSimbolos) {
        $this->tabela = $tabelaSimbolos;
        $this->dataSegment = ".data\n";
        $this->textSegment = ".text\n.globl main\nmain:\n";
        $this->labelCounter = 0;
        $this->stringCounter = 0;
        $this->varOffsets = [];
    }

    public function gerar(array $ast) {

        // declara todas as variáveis
        foreach ($this->tabela as $sym) {
            $this->declareVariable($sym);
        }

        if ($ast['type'] !== 'PROGRAM') {
            throw new Exception("AST inválida");
        }

        foreach ($ast['body'] as $node) {
            $this->generateNode($node);
        }
    }

    private function generateNode(array $node) {
        switch ($node['type']) {

            case 'ASSIGN':
                $this->generateAssignAST($node);
                break;

            case 'IF':
                $this->generateIfAST($node);
                break;

            case 'PRINT':
                $this->generatePrintAST($node);
                break;

            default:
                throw new Exception("Nó não suportado: {$node['type']}");
        }
    }

    private function declareVariable(Symbol $sym) {
        $name = $sym->name;
        $type = $sym->type;

        if (isset($this->varOffsets[$name])) return;

        switch ($type) {
            case 'INT':
                $this->dataSegment .= "$name: .word 0\n";
                break;
            case 'FLOAT':
                $this->dataSegment .= "$name: .float 0.0\n";
                break;
            case 'STRING':
                $this->dataSegment .= "$name: .asciiz \"\"\n";
                break;
            default:
                $this->dataSegment .= "$name: .word 0\n";
        }

        $this->varOffsets[$name] = $name;
    }

    private function generateAssignAST(array $node) {
        $lhs = $node['lhs'];   // nome da variável
        $rhs = $node['rhs'];   // expressão

        $this->generateExpr($rhs, "\$t0");
        $this->textSegment .= "sw \$t0, $lhs\n";
    }

    private function generateIfAST(array $node) {
        $lblElse = $this->newLabel("ELSE");
        $lblEnd  = $this->newLabel("ENDIF");

        $this->generateCondExpr($node['cond'], "\$t0");
        $this->textSegment .= "beq \$t0, \$zero, $lblElse\n";

        foreach ($node['then'] as $stmt) {
            $this->generateNode($stmt);
        }

        $this->textSegment .= "j $lblEnd\n";
        $this->textSegment .= "$lblElse:\n";

        if (isset($node['else'])) {
            foreach ($node['else'] as $stmt) {
                $this->generateNode($stmt);
            }
        }

        $this->textSegment .= "$lblEnd:\n";
    }

    private function generateExpr(array $expr, $destReg) {
        switch ($expr['type']) {

            case 'CONST':
                if (is_numeric($expr['value'])) {
                    $this->textSegment .= "li $destReg, {$expr['value']}\n";
                } else {
                    // string literal
                    $label = "str" . $this->stringCounter++;
                    $this->dataSegment .= "$label: .asciiz \"{$expr['value']}\"\n";
                    $this->textSegment .= "la $destReg, $label\n";
                }
                break;

            case 'ID':
                $this->textSegment .= "lw $destReg, {$expr['value']}\n";
                break;

            case 'BINOP':
                $this->generateExpr($expr['left'], "\$t1");
                $this->generateExpr($expr['right'], "\$t2");

                switch ($expr['op']) {
                    case '+':
                        $this->textSegment .= "add $destReg, \$t1, \$t2\n";
                        break;
                    case '-':
                        $this->textSegment .= "sub $destReg, \$t1, \$t2\n";
                        break;
                    case '*':
                        $this->textSegment .= "mul $destReg, \$t1, \$t2\n";
                        break;
                    case '/':
                        $this->textSegment .= "div \$t1, \$t2\n";
                        $this->textSegment .= "mflo $destReg\n";
                        break;
                }
                break;
        }
    }

    private function generateCondExpr(array $expr, $destReg) {
        $this->generateExpr($expr['left'], "\$t1");
        $this->generateExpr($expr['right'], "\$t2");

        switch ($expr['op']) {
            case '>':
                $this->textSegment .= "slt $destReg, \$t2, \$t1\n";
                break;
            case '<':
                $this->textSegment .= "slt $destReg, \$t1, \$t2\n";
                break;
            case '==':
                $this->textSegment .= "sub \$t3, \$t1, \$t2\n";
                $this->textSegment .= "sltiu $destReg, \$t3, 1\n";
                break;
            default:
                throw new Exception("Operador relacional inválido");
        }
    }

    private function generatePrintAST(array $node) {
        $expr = $node['expr'];

        if ($expr['type'] === 'CONST' && !is_numeric($expr['value'])) {
            // print string
            $this->generateExpr($expr, "\$a0");
            $this->textSegment .= "li \$v0, 4\nsyscall\n";
        } else {
            // print inteiro
            $this->generateExpr($expr, "\$a0");
            $this->textSegment .= "li \$v0, 1\nsyscall\n";
        }

        // quebra de linha
        $this->textSegment .= "li \$v0, 4\nla \$a0, newline\nsyscall\n";
    }

    private function newLabel($prefix) {
        return $prefix . ($this->labelCounter++);
    }

    public function getCode(): string {
        return
            $this->dataSegment .
            "newline: .asciiz \"\\n\"\n\n" .
            $this->textSegment .
            "li \$v0, 10\nsyscall\n";
    }
}
