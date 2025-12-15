<?php
class Token {
        //Classe que representa um Token gerado pelo Analisador Léxico / Cada token possui: Tipo(token), Lexema(lexema), Posição(pos), Linha(line)

    private string $token; // Tipo do token (ID, IF, CONST...)
    private string $lexema; // Texto original do código-fonte
    private int $pos, $line; // Posição do token e número da linha    

    public function __construct(string $tk, string $lex, int $pos, int $line) {
        $this->token = $tk;
        $this->lexema = $lex;
        $this->pos = $pos;
        $this->line = $line;
    }
    public function getToken(): string { return $this->token; }
    public function getLexema(): string { return $this->lexema; }
    public function getPos(): int { return $this->pos; }
    public function getLine(): int { return $this->line; }

    public function __toString(): string {
        return "<{$this->token}, '{$this->lexema}'> (linha {$this->line}, pos {$this->pos})";
    }
}