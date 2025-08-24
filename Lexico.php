<?php

require_once("token.php");

class Lexico
{
    private $afd = [
        "q0" => [
            " "=>"q5", "("=>"q6", ")"=>"q7", "{"=>"q9", ">"=>"q8",
            "0"=>"q3","1"=>"q3","2"=>"q3","3"=>"q3","4"=>"q3","5"=>"q3","6"=>"q3","7"=>"q3","8"=>"q3","9"=>"q3",
            "a"=>"q4","b"=>"q4","c"=>"q4","d"=>"q4","e"=>"q4","f"=>"q4","g"=>"q4","h"=>"q4",
            "i"=>"q1","j"=>"q4","k"=>"q4","l"=>"q4","m"=>"q4","n"=>"q4","o"=>"q4","p"=>"q4","q"=>"q4","r"=>"q4",
            "s"=>"q4","t"=>"q4","u"=>"q4","v"=>"q4","w"=>"q4","x"=>"q4","y"=>"q4","z"=>"q4",
            ";"=>"q10","="=>"q11"
        ],
        "q1"=>[
            "0"=>"q4","1"=>"q4","2"=>"q4","3"=>"q4","4"=>"q4","5"=>"q4","6"=>"q4","7"=>"q4","8"=>"q4","9"=>"q4",
            "a"=>"q4","b"=>"q4","c"=>"q4","d"=>"q4","e"=>"q4","f"=>"q2","g"=>"q4","h"=>"q4","i"=>"q4","j"=>"q4",
            "k"=>"q4","l"=>"q4","m"=>"q4","n"=>"q4","o"=>"q4","p"=>"q4","q"=>"q4","r"=>"q4","s"=>"q4","t"=>"q4",
            "u"=>"q4","v"=>"q4","w"=>"q4","x"=>"q4","y"=>"q4","z"=>"q4"
        ],
        "q2"=>array_fill_keys(range('a', 'z'), "q4") + array_fill_keys(range('0', '9'), "q4"),
        "q3"=>array_fill_keys(range('0', '9'), "q3"),
        "q4"=>array_fill_keys(range('a', 'z'), "q4") + array_fill_keys(range('0', '9'), "q4"),
        "q5"=>[], "q6"=>[], "q7"=>[], "q8"=>[], "q9"=>[], "q10"=>[], "q11"=>[]
    ];

    private $finais = [
        "q1"=>"ID", "q2"=>"IF", "q3"=>"CONST", "q4"=>"ID",
        "q5"=>"WS", "q6"=>"AP", "q7"=>"FP", "q8"=>"MAIOR",
        "q9"=>"INIBLOCO", "q10"=>"PV", "q11"=>"ATR"
    ];

    private $lista_tokens = [];

    public function scan(string $entrada): void
    {
        $estado = "q0";
        $lexema = "";
        $pos = 0;
        $i = 0;

        while ($i < strlen($entrada)) {
            $char = $entrada[$i];

            if (isset($this->afd[$estado][$char])) {
                $estado = $this->afd[$estado][$char];
                $lexema .= $char;
                $pos++;
                $i++;
            } elseif (isset($this->finais[$estado])) {
                $this->registrarToken($estado, $lexema, $pos);
                $estado = "q0";
                $lexema = "";
            } else {
                throw new Exception("Erro léxico: token inválido '{$lexema}{$char}' na posição {$i}");
            }
        }

        if (isset($this->finais[$estado])) {
            $this->registrarToken($estado, $lexema, $pos);
        }
    }

    private function registrarToken(string $estado, string $lexema, int $pos): void
    {
        if ($this->finais[$estado] !== "WS") {
            $this->lista_tokens[] = new Token($this->finais[$estado], $lexema, $pos);
        }
    }

    public function getTokens(): array
    {
        return $this->lista_tokens;
    }

    public function __toString(): string
    {
        return implode("\n", $this->lista_tokens);
    }
}