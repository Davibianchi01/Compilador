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

class LexError {
    public string $mensagem;
    public string $lexema;
    public int $linha;
    public int $coluna;

    public function __construct(string $mensagem, string $lexema, int $linha, int $coluna) {
        $this->mensagem = $mensagem;
        $this->lexema = $lexema;
        $this->linha = $linha;
        $this->coluna = $coluna;
    }

    public function __toString(): string {
        return sprintf("Erro léxico: %s (\"%s\") @%d:%d", $this->mensagem, $this->lexema, $this->linha, $this->coluna);
    }
}

class Lexer {
    private string $src;
    private int $start = 0;
    private int $current = 0;
    private int $linha = 1;
    private int $coluna = 1;

    public array $tokens = [];
    public array $errors = [];

    private const KEYWORDS = [
        'se' => 'SE', 'senão' => 'SENAO', 'senao' => 'SENAO',
        'para' => 'PARA', 'faça' => 'FACA', 'faca' => 'FACA',
        'enquanto' => 'ENQUANTO',
        'escreva' => 'ESCREVA', 'leia' => 'LEIA',
        'inteiro' => 'TIPO_INTEIRO', 'flutuante' => 'TIPO_FLUTUANTE',
        'lógico' => 'TIPO_LOGICO', 'logico' => 'TIPO_LOGICO',
        'cadeia' => 'TIPO_CADEIA',
        'inicio' => 'INICIO', 'fim' => 'FIM',
        'verdadeiro' => 'BOOLEAN', 'falso' => 'BOOLEAN',
        'true' => 'BOOLEAN', 'false' => 'BOOLEAN',
    ];

    public function __construct(string $source) {
        $this->src = $source;
    }

    public function scan(): void {
        while (!$this->isAtEnd()) {
            $this->start = $this->current;
            $this->scanToken();
        }
        $this->tokens[] = new Token('EOF', '', $this->linha, $this->coluna);
    }

    private function scanToken(): void {
        $c = $this->advance();
        switch ($c) {
            case ' ': case "\r": case "\t": return;
            case "\n": $this->linha++; $this->coluna = 1; return;
            case '/':
                if ($this->match('/')) {
                    while (!$this->isAtEnd() && $this->peek() !== "\n") $this->advance();
                    return;
                } elseif ($this->match('*')) {
                    $this->consumeBlockComment();
                    return;
                } else {
                    $this->addToken('DIV');
                    return;
                }
            case '+':
                if ($this->match('+')) { $this->addToken('INC'); }
                else { $this->addToken('ADD'); }
                return;
            case '-':
                if ($this->match('-')) { $this->addToken('DEC'); }
                else { $this->addToken('SUB'); }
                return;
            case '*': $this->addToken('MUL'); return;
            case '&': $this->addToken('CONCAT'); return;
            case '=': $this->addToken('ATRIB'); return;
            case '(': $this->addToken('AP'); return;
            case ')': $this->addToken('FP'); return;
            case '[': $this->addToken('ACOL'); return;
            case ']': $this->addToken('FCOL'); return;
            case '{': $this->addToken('ACH'); return;
            case '}': $this->addToken('FCH'); return;
            case '>': $this->addToken($this->match('=') ? 'MAIORIGUAL' : 'MAIOR'); return;
            case '<': $this->addToken($this->match('=') ? 'MENORIGUAL' : 'MENOR'); return;
            case '"': $this->string(); return;
        }

        if ($this->isAlpha($c) || $c === '_') {
            $this->identifier();
            return;
        }

        if ($this->isDigit($c)) {
            $this->number();
            return;
        }

        $lex = $this->currentLexeme();
        $this->error("caractere inválido", $lex);
    }

    private function identifier(): void {
        while ($this->isAlphaNumeric($this->peek()) || $this->peek() === '_') $this->advance();
        $text = $this->currentLexeme();
        $lower = mb_strtolower($text, 'UTF-8');
        if (isset(self::KEYWORDS[$lower])) {
            $tipo = self::KEYWORDS[$lower];
            if ($tipo === 'BOOLEAN') {
                $this->tokens[] = new Token('CONST_BOOLEAN', $text, $this->linha, $this->tokenColumn());
            } else {
                $this->tokens[] = new Token($tipo, $text, $this->linha, $this->tokenColumn());
            }
        } else {
            $this->tokens[] = new Token('ID', $text, $this->linha, $this->tokenColumn());
        }
    }

    private function number(): void {
        while ($this->isDigit($this->peek())) $this->advance();
        if ($this->peek() === '.' && $this->isDigit($this->peekNext())) {
            $this->advance();
            while ($this->isDigit($this->peek())) $this->advance();
            $this->tokens[] = new Token('CONST_FLOAT', $this->currentLexeme(), $this->linha, $this->tokenColumn());
            return;
        }
        $this->tokens[] = new Token('CONST_INT', $this->currentLexeme(), $this->linha, $this->tokenColumn());
    }

    private function string(): void {
        $escaped = false;
        while (!$this->isAtEnd()) {
            $ch = $this->advance();
            if ($escaped) { $escaped = false; continue; }
            if ($ch === "\n") { $this->linha++; $this->coluna = 1; }
            if ($ch === '\\') { $escaped = true; continue; }
            if ($ch === '"') {
                $this->tokens[] = new Token('CONST_STRING', $this->currentLexeme(), $this->linha, $this->tokenColumn());
                return;
            }
        }
        $this->error('string não terminada', $this->currentLexeme());
    }

    private function consumeBlockComment(): void {
        while (!$this->isAtEnd()) {
            $c = $this->advance();
            if ($c === "\n") { $this->linha++; $this->coluna = 1; }
            if ($c === '*' && $this->match('/')) return;
        }
        $this->error('comentário de bloco não terminado', '/*');
    }

    private function addToken(string $tipo): void {
        $this->tokens[] = new Token($tipo, $this->currentLexeme(), $this->linha, $this->tokenColumn());
    }

    private function error(string $mensagem, string $lexema): void {
        $this->errors[] = new LexError($mensagem, $lexema, $this->linha, $this->tokenColumn());
    }

    private function isAtEnd(): bool { return $this->current >= strlen($this->src); }

    private function advance(): string {
        $ch = $this->src[$this->current] ?? "";
        $this->current++;
        $this->coluna++;
        return $ch;
    }

    private function match(string $expected): bool {
        if ($this->isAtEnd()) return false;
        if ($this->src[$this->current] !== $expected) return false;
        $this->current++;
        $this->coluna++;
        return true;
    }

    private function peek(): string {
        return $this->isAtEnd() ? "" : $this->src[$this->current];
    }

    private function peekNext(): string {
        $idx = $this->current + 1;
        return ($idx >= strlen($this->src)) ? "" : $this->src[$idx];
    }

    private function currentLexeme(): string {
        return substr($this->src, $this->start, $this->current - $this->start);
    }

    private function tokenColumn(): int {
        $length = $this->current - $this->start;
        return $this->coluna - $length;
    }

    private function isDigit(string $c): bool { return $c >= '0' && $c <= '9'; }
    private function isAlpha(string $c): bool {
        if ($c === '') return false;
        return preg_match('/[A-Za-z_À-ÖØ-öø-ÿ]/u', $c) === 1;
    }
    private function isAlphaNumeric(string $c): bool { return $this->isAlpha($c) || $this->isDigit($c); }
}

$codigo = <<<CODE
inicio
  inteiro x = 10
  flutuante y = 3.14
  lógico ok = verdadeiro
  cadeia s = "ola\n" & "mundo"
  x++
  y = y / 2
  se (x > 5) {
    escreva(s)
  } senão {
    leia(s)
  }
fim
CODE;

$lexer = new Lexer($codigo);
$lexer->scan();

foreach ($lexer->tokens as $t) {
    echo $t, PHP_EOL;
}

echo str_repeat('-', 40), PHP_EOL;
if (count($lexer->errors) > 0) {
    foreach ($lexer->errors as $e) {
        echo $e, PHP_EOL;
    }
} else {
    echo "Sem erros léxicos.", PHP_EOL;
}
