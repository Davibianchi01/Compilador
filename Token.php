<?php

class Token {
    public string $tipo;
    public string $lexema;
    public int $linha;
    public int $coluna;

    public function __construct(string $tipo, string $lexema, int $linha, int $coluna) {
        $this->tipo = $tipo;
        $this->lexema = $lexema;
        $this->linha = $linha;
        $this->coluna = $coluna;
    }

    public function __toString(): string {
        return sprintf("<%s,%s> @%d:%d", $this->tipo, $this->lexema, $this->linha, $this->coluna);
    }
}

$tokens = [
    new Token("TIPO_INTEIRO", "inteiro", 1, 1),
    new Token("ID", "x", 1, 9),
    new Token("ATRIB", "=", 1, 11),
    new Token("CONST_INT", "10", 1, 13),
];

foreach ($t
