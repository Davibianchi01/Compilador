<?php
class Token{ 
    //Classe que representa um Token gerado pelo Analisador Léxico / Cada token possui: Tipo(token), Lexema(lexema), Posição(pos), Linha(line)

    private $token, $lexema, $pos, $line; //Tipo do token - Texto capturado do código-fonte - Posição do token na linha - Número da linha

        public function __construct($tk,$lex, $pos,$line){
        $this->token = $tk;
        $this->lexema = $lex;
        $this->pos = $pos;
        $this->line = $line;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getLexema(): string
    {
        return $this->lexema;
    }

    public function getPos(): int
    {
        return $this->pos;
    }

    public function getLine(): int
    {
        return $this->line;
    }

    public function __toString(): string
    {
        return "<{$this->token}, {$this->lexema}>";
    }
}