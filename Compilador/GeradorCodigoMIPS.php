<?php
require_once("AnalisadorSemantico.php");

class GeradorCodigoMIPS {
    private $semantico;
    private $code;           // código MIPS gerado
    private $dataSegment;    // seção .data
    private $textSegment;    // seção .text
    private $labelCounter;   // contador para labels únicas
    private $varOffsets;     // mapa de variáveis -> registradores ou offsets

    public function __construct(SemanticAnalyzer $semantico) {
        $this->semantico = $semantico;
        $this->code = "";
        $this->dataSegment = ".data\n";
        $this->textSegment = ".text\n.globl main\nmain:\n";
        $this->labelCounter = 0;
        $this->varOffsets = [];
    }

    private function newLabel($prefix = "L") {
        $lbl = $prefix . $this->labelCounter;
        $this->labelCounter++;
        return $lbl;
    }

    // Declara variáveis no segmento .data
    public function declareVariable(Symbol $sym) {
        $name = $sym->name;
        $type = $sym->type;

        if (!isset($this->varOffsets[$name])) {
            if ($type === 'INT') {
    $mipsType = ".word 0";
} elseif ($type === 'FLOAT') {
    $mipsType = ".float 0.0";
} elseif ($type === 'STRING') {
    $mipsType = ".asciiz \"\"";
} else {
    $mipsType = ".word 0";
}
            $this->dataSegment .= "$name: $mipsType\n";
            $this->varOffsets[$name] = $name;
        }
    }

    // Atribuição simples: x = y + z
    public function generateAssign(Symbol $lhs, $rhsExpr) {
        // $rhsExpr pode ser: ['type'=>'ID'|'CONST'|'BINOP', 'value'=>..., 'op'=>..., 'left'=>..., 'right'=>...]
        $lhsName = $lhs->name;

        if ($rhsExpr['type'] === 'CONST') {
            $val = $rhsExpr['value'];
            $this->textSegment .= "li \$t0, $val\n";
            $this->textSegment .= "sw \$t0, $lhsName\n";
        } elseif ($rhsExpr['type'] === 'ID') {
            $src = $rhsExpr['value'];
            $this->textSegment .= "lw \$t0, $src\n";
            $this->textSegment .= "sw \$t0, $lhsName\n";
        } elseif ($rhsExpr['type'] === 'BINOP') {
            $left = $rhsExpr['left'];
            $right = $rhsExpr['right'];
            $op = $rhsExpr['op'];

            // recursivamente gerar código para left/right
            $this->generateExpr($left, "\$t1");
            $this->generateExpr($right, "\$t2");

            switch (strtoupper($op)) {
                case '+': $this->textSegment .= "add \$t0, \$t1, \$t2\n"; break;
                case '*': $this->textSegment .= "mul \$t0, \$t1, \$t2\n"; break;
                case '-': $this->textSegment .= "sub \$t0, \$t1, \$t2\n"; break;
                case '/': $this->textSegment .= "div \$t1, \$t2\nmflo \$t0\n"; break;
                default: throw new Exception("Operador não suportado: $op");
            }
            $this->textSegment .= "sw \$t0, $lhsName\n";
        }
    }

    private function generateExpr($expr, $destReg = "\$t0") {
        if ($expr['type'] === 'CONST') {
            $this->textSegment .= "li $destReg, {$expr['value']}\n";
        } elseif ($expr['type'] === 'ID') {
            $this->textSegment .= "lw $destReg, {$expr['value']}\n";
        } elseif ($expr['type'] === 'BINOP') {
            $this->generateExpr($expr['left'], "\$t1");
            $this->generateExpr($expr['right'], "\$t2");
            switch (strtoupper($expr['op'])) {
                case '+': $this->textSegment .= "add $destReg, \$t1, \$t2\n"; break;
                case '*': $this->textSegment .= "mul $destReg, \$t1, \$t2\n"; break;
                case '-': $this->textSegment .= "sub $destReg, \$t1, \$t2\n"; break;
                case '/': $this->textSegment .= "div \$t1, \$t2\nmflo $destReg\n"; break;
            }
        }
    }

    // Gera código para if(cond){ ... }
    public function generateIf($condExpr, $trueCodeCallback) {
        $labelElse = $this->newLabel("ELSE");
        $labelEnd  = $this->newLabel("ENDIF");

        // gera expressão de condição em $t0
        $this->generateExpr($condExpr, "\$t0");

        // se zero, pula para else
        $this->textSegment .= "beq \$t0, \$zero, $labelElse\n";

        // bloco verdadeiro
        $trueCodeCallback($this);

        $this->textSegment .= "j $labelEnd\n";
        $this->textSegment .= "$labelElse:\n";

        $this->textSegment .= "$labelEnd:\n";
    }

    // Função printf simples para inteiros
    public function generatePrint($expr) {
        $this->generateExpr($expr, "\$a0"); // coloca valor em $a0
        $this->textSegment .= "li \$v0, 1\nsyscall\n"; // syscall print_int
        $this->textSegment .= "li \$v0, 4\nla \$a0, newline\nsyscall\n"; // quebra linha
    }

    public function getCode(): string {
        $finalCode = $this->dataSegment;
        $finalCode .= "newline: .asciiz \"\\n\"\n";
        $finalCode .= $this->textSegment;
        $finalCode .= "li \$v0, 10\nsyscall\n"; // exit
        return $finalCode;
    }
}
