    <?php
    require_once("Token.php");
    class Lexico{

        //Classe que representa o Analisador Léxico usando um AFD para reconhecer tokens
        private array $afd;
        private array $finais;
        private array $tokens;
        private int $line;
        private int $column;
        private string $estado;
        private string $lexema;

        public function __construct()
        {
            $this->tokens = [];
            $this->line   = 1;
            $this->column = 1;
            $this->estado = "q0";
            $this->lexema = "";

        //TABELA DO AUTÔMATO FINITO
        //Cada estado contém transições: caractere → próximo estado

            $this->afd = [
                "q0" => [
                    " "=>"q5","("=>"q6",")"=>"q7","{"=>"q9",">"=>"q8","}"=>"q12",
                    "0"=>"q3","1"=>"q3","2"=>"q3","3"=>"q3","4"=>"q3","5"=>"q3",
                    "6"=>"q3","7"=>"q3","8"=>"q3","9"=>"q3",
                    "a"=>"q4","b"=>"q19","c"=>"q15","d"=>"q4","e"=>"q4","f"=>"q4",
                    "g"=>"q4","h"=>"q4","i"=>"q1","j"=>"q4","k"=>"q4","l"=>"q4",
                    "m"=>"q4","n"=>"q4","o"=>"q4","p"=>"q4","q"=>"q4","r"=>"q4",
                    "s"=>"q4","t"=>"q4","u"=>"q4","v"=>"q4","w"=>"q4","x"=>"q4",
                    "y"=>"q4","z"=>"q4",";"=>"q10","="=>"q11","'"=>"q23"
                ],
                "q1"=>["0"=>"q4","1"=>"q4","2"=>"q4","3"=>"q4","4"=>"q4","5"=>"q4","6"=>"q4","7"=>"q4","8"=>"q4",
                    "9"=>"q4","a"=>"q4","b"=>"q4","c"=>"q4","d"=>"q4","e"=>"q4","f"=>"q2","g"=>"q4","h"=>"q4",
                    "i"=>"q4","j"=>"q4","k"=>"q4","l"=>"q4","m"=>"q4","n"=>"q13","o"=>"q4","p"=>"q4","q"=>"q4",
                    "r"=>"q4","s"=>"q4","t"=>"q4","u"=>"q4","v"=>"q4","w"=>"q4","x"=>"q4","y"=>"q4","z"=>"q4"],

                "q2"=>["0"=>"q4","1"=>"q4","2"=>"q4","3"=>"q4","4"=>"q4","5"=>"q4","6"=>"q4","7"=>"q4","8"=>"q4",
                    "9"=>"q4","a"=>"q4","b"=>"q4","c"=>"q4","d"=>"q4","e"=>"q4","f"=>"q4","g"=>"q4","h"=>"q4",
                    "i"=>"q4","j"=>"q4","k"=>"q4","l"=>"q4","m"=>"q4","n"=>"q4","o"=>"q4","p"=>"q4","q"=>"q4",
                    "r"=>"q4","s"=>"q4","t"=>"q4","u"=>"q4","v"=>"q4","w"=>"q4","x"=>"q4","y"=>"q4","z"=>"q4"],

                "q3"=>["0"=>"q3","1"=>"q3","2"=>"q3","3"=>"q3","4"=>"q3","5"=>"q3","6"=>"q3","7"=>"q3","8"=>"q3","9"=>"q3"],

                "q4"=>["0"=>"q4","1"=>"q4","2"=>"q4","3"=>"q4","4"=>"q4","5"=>"q4","6"=>"q4","7"=>"q4","8"=>"q4","9"=>"q4",
                    "a"=>"q4","b"=>"q4","c"=>"q4","d"=>"q4","e"=>"q4","f"=>"q4","g"=>"q4","h"=>"q4","i"=>"q4","j"=>"q4",
                    "k"=>"q4","l"=>"q4","m"=>"q4","n"=>"q4","o"=>"q4","p"=>"q4","q"=>"q4","r"=>"q4","s"=>"q4","t"=>"q4",
                    "u"=>"q4","v"=>"q4","w"=>"q4","x"=>"q4","y"=>"q4","z"=>"q4"],

                "q5"=>[], "q6"=>[], "q7"=>[], "q8"=>[], "q9"=>[], "q10"=>[], "q11"=>[], "q12"=>[],
                "q13"=>["0"=>"q4","1"=>"q4","2"=>"q4","3"=>"q4","4"=>"q4","5"=>"q4","6"=>"q4","7"=>"q4","8"=>"q4",
                        "9"=>"q4","a"=>"q4","b"=>"q4","c"=>"q4","d"=>"q4","e"=>"q4","f"=>"q4","g"=>"q4","h"=>"q4",
                        "i"=>"q4","j"=>"q4","k"=>"q4","l"=>"q4","m"=>"q4","n"=>"q4","o"=>"q4","p"=>"q4","q"=>"q4",
                        "r"=>"q4","s"=>"q4","t"=>"q14","u"=>"q4","v"=>"q4","w"=>"q4","x"=>"q4","y"=>"q4","z"=>"q4"],

                "q14"=>["0"=>"q4","1"=>"q4","2"=>"q4","3"=>"q4","4"=>"q4","5"=>"q4","6"=>"q4","7"=>"q4","8"=>"q4",
                        "9"=>"q4","a"=>"q4","b"=>"q4","c"=>"q4","d"=>"q4","e"=>"q4","f"=>"q4","g"=>"q4","h"=>"q4",
                        "i"=>"q4","j"=>"q4","k"=>"q4","l"=>"q4","m"=>"q4","n"=>"q4","o"=>"q4","p"=>"q4","q"=>"q4",
                        "r"=>"q4","s"=>"q4","t"=>"q4","u"=>"q4","v"=>"q4","w"=>"q4","x"=>"q4","y"=>"q4","z"=>"q4"],

                "q15"=>["0"=>"q4","1"=>"q4","2"=>"q4","3"=>"q4","4"=>"q4","5"=>"q4","6"=>"q4","7"=>"q4","8"=>"q4",
                        "9"=>"q4","a"=>"q4","b"=>"q4","c"=>"q4","d"=>"q4","e"=>"q4","f"=>"q4","g"=>"q4","h"=>"q16",
                        "i"=>"q4","j"=>"q4","k"=>"q4","l"=>"q4","m"=>"q4","n"=>"q4","o"=>"q4","p"=>"q4","q"=>"q4",
                        "r"=>"q4","s"=>"q4","t"=>"q4","u"=>"q4","v"=>"q4","w"=>"q4","x"=>"q4","y"=>"q4","z"=>"q4"],

                "q16"=>["0"=>"q4","1"=>"q4","2"=>"q4","3"=>"q4","4"=>"q4","5"=>"q4","6"=>"q4","7"=>"q4","8"=>"q4",
                        "9"=>"q4","a"=>"q17","b"=>"q4","c"=>"q4","d"=>"q4","e"=>"q4","f"=>"q4","g"=>"q4","h"=>"q4",
                        "i"=>"q4","j"=>"q4","k"=>"q4","l"=>"q4","m"=>"q4","n"=>"q4","o"=>"q4","p"=>"q4","q"=>"q4",
                        "r"=>"q4","s"=>"q4","t"=>"q4","u"=>"q4","v"=>"q4","w"=>"q4","x"=>"q4","y"=>"q4","z"=>"q4"],

                "q17"=>["0"=>"q4","1"=>"q4","2"=>"q4","3"=>"q4","4"=>"q4","5"=>"q4","6"=>"q4","7"=>"q4","8"=>"q4",
                        "9"=>"q4","a"=>"q4","b"=>"q4","c"=>"q4","d"=>"q4","e"=>"q4","f"=>"q4","g"=>"q4","h"=>"q4",
                        "i"=>"q4","j"=>"q4","k"=>"q4","l"=>"q4","m"=>"q4","n"=>"q4","o"=>"q4","p"=>"q4","q"=>"q4",
                        "r"=>"q18","s"=>"q4","t"=>"q4","u"=>"q4","v"=>"q4","w"=>"q4","x"=>"q4","y"=>"q4","z"=>"q4"],

                "q18"=>["0"=>"q4","1"=>"q4","2"=>"q4","3"=>"q4","4"=>"q4","5"=>"q4","6"=>"q4","7"=>"q4","8"=>"q4",
                        "9"=>"q4","a"=>"q4","b"=>"q4","c"=>"q4","d"=>"q4","e"=>"q4","f"=>"q4","g"=>"q4","h"=>"q4",
                        "i"=>"q4","j"=>"q4","k"=>"q4","l"=>"q4","m"=>"q4","n"=>"q4","o"=>"q4","p"=>"q4","q"=>"q4",
                        "r"=>"q4","s"=>"q4","t"=>"q4","u"=>"q4","v"=>"q4","w"=>"q4","x"=>"q4","y"=>"q4","z"=>"q4"],

                "q19"=>["0"=>"q4","1"=>"q4","2"=>"q4","3"=>"q4","4"=>"q4","5"=>"q4","6"=>"q4","7"=>"q4","8"=>"q4",
                        "9"=>"q4","a"=>"q4","b"=>"q4","c"=>"q4","d"=>"q4","e"=>"q4","f"=>"q4","g"=>"q4","h"=>"q4",
                        "i"=>"q4","j"=>"q4","k"=>"q4","l"=>"q4","m"=>"q4","n"=>"q4","o"=>"q20","p"=>"q4","q"=>"q4",
                        "r"=>"q4","s"=>"q4","t"=>"q4","u"=>"q4","v"=>"q4","w"=>"q4","x"=>"q4","y"=>"q4","z"=>"q4"],

                "q20"=>["0"=>"q4","1"=>"q4","2"=>"q4","3"=>"q4","4"=>"q4","5"=>"q4","6"=>"q4","7"=>"q4","8"=>"q4",
                        "9"=>"q4","a"=>"q4","b"=>"q4","c"=>"q4","d"=>"q4","e"=>"q4","f"=>"q4","g"=>"q4","h"=>"q4",
                        "i"=>"q4","j"=>"q4","k"=>"q4","l"=>"q4","m"=>"q4","n"=>"q4","o"=>"q21","p"=>"q4","q"=>"q4",
                        "r"=>"q4","s"=>"q4","t"=>"q4","u"=>"q4","v"=>"q4","w"=>"q4","x"=>"q4","y"=>"q4","z"=>"q4"],

                "q21"=>["0"=>"q4","1"=>"q4","2"=>"q4","3"=>"q4","4"=>"q4","5"=>"q4","6"=>"q4","7"=>"q4","8"=>"q4",
                        "9"=>"q4","a"=>"q4","b"=>"q4","c"=>"q4","d"=>"q4","e"=>"q4","f"=>"q4","g"=>"q4","h"=>"q4",
                        "i"=>"q4","j"=>"q4","k"=>"q4","l"=>"q22","m"=>"q4","n"=>"q4","o"=>"q4","p"=>"q4","q"=>"q4",
                        "r"=>"q4","s"=>"q4","t"=>"q4","u"=>"q4","v"=>"q4","w"=>"q4","x"=>"q4","y"=>"q4","z"=>"q4"],

                "q22"=>["0"=>"q4","1"=>"q4","2"=>"q4","3"=>"q4","4"=>"q4","5"=>"q4","6"=>"q4","7"=>"q4","8"=>"q4",
                        "9"=>"q4","a"=>"q4","b"=>"q4","c"=>"q4","d"=>"q4","e"=>"q4","f"=>"q4","g"=>"q4","h"=>"q4",
                        "i"=>"q4","j"=>"q4","k"=>"q4","l"=>"q4","m"=>"q4","n"=>"q4","o"=>"q4","p"=>"q4","q"=>"q4",
                        "r"=>"q4","s"=>"q4","t"=>"q4","u"=>"q4","v"=>"q4","w"=>"q4","x"=>"q4","y"=>"q4","z"=>"q4"],

                "q23"=>[
                    "0"=>"q23","1"=>"q23","2"=>"q23","3"=>"q23","4"=>"q23","5"=>"q23","6"=>"q23",
                    "7"=>"q23","8"=>"q23","9"=>"q23","a"=>"q23","b"=>"q23","c"=>"q23","d"=>"q23",
                    "e"=>"q23","f"=>"q23","g"=>"q23","h"=>"q23","i"=>"q23","j"=>"q23","k"=>"q23",
                    "l"=>"q23","m"=>"q23","n"=>"q23","o"=>"q23","p"=>"q23","q"=>"q23","r"=>"q23",
                    "s"=>"q23","t"=>"q23","u"=>"q23","v"=>"q23","w"=>"q23","x"=>"q23","y"=>"q23",
                    "z"=>"q23","'"=>"q24"
                ],

                "q24"=>[]
            ];
            $this->finais = [
                "q1"=>"ID","q2"=>"IF","q3"=>"CONST","q4"=>"ID","q5"=>"WS","q6"=>"AP","q7"=>"FP",
                "q8"=>"MAIOR","q9"=>"AB","q12"=>"FB","q10"=>"PV","q11"=>"ATR","q14"=>"INT",
                "q18"=>"CHAR","q22"=>"BOOL","q24"=>"STR"
            ];
        }

        private function emitirTokenFinal()
        {
            if (!isset($this->finais[$this->estado])) {
                return;
            }

            $tipo = $this->finais[$this->estado];

            if ($tipo !== "WS") {
                $this->tokens[] = new Token(
        $tipo,
        $this->lexema,
        max(1, $this->column - strlen($this->lexema)),
        $this->line                                    
    );
            }

            $this->estado = "q0";
            $this->lexema = "";
        }
        public function scan(string $input)
        {  
             echo ">> Léxico iniciado\n";

            $input = str_replace("\r", "", $input); // remove todos os \r
$linhas = explode("\n", $input);


 foreach ($linhas as $linha) {
    $this->column = 1;
    $i = 0;
    
    while ($i < strlen($linha)) {

$c = $linha[$i];

// trata espaço como delimitador universal
while ($i < strlen($linha)) {

    $c = $linha[$i];

    // existe transição no AFD?
    if (isset($this->afd[$this->estado][$c])) {

        // avança no autômato
        $this->estado = $this->afd[$this->estado][$c];
        $this->lexema .= $c;

        $i++;
        $this->column++;
    }
    else {
        // tenta finalizar token
        if (isset($this->finais[$this->estado])) {
            $this->emitirTokenFinal();
            // NÃO incrementa $i (reprocessa o caractere)
        }
        else {
            throw new Exception(
                "Erro Léxico: caractere inválido '".htmlspecialchars($c)
                ."' na linha {$this->line}, coluna {$this->column}"
            );
        }
    }
}

    }
    if (isset($this->finais[$this->estado])) {
    $this->emitirTokenFinal();
}

                $this->line++;
            }

            if (isset($this->finais[$this->estado])) {
                $this->emitirTokenFinal();
            }

            $this->tokens[] = new Token("$", "$", $this->line, 1);
            echo ">> Tokens gerados:\n";
            foreach ($this->tokens as $tk) {
            echo $tk . PHP_EOL;
            }
        }

        public function nextToken()
        {
            $tk = current($this->tokens);
            next($this->tokens);
            return $tk;
        }

        public function prevToken()
        {
            if (current($this->tokens)) {
                return prev($this->tokens);
            }
            return end($this->tokens);
        }

        public function getTokens(): array
        {
            return $this->tokens;
        }
        public function __toString(): string
{
    $out = "";
    foreach ($this->tokens as $tk) {
        $out .= $tk . PHP_EOL;
    }
    return $out;
}
    }