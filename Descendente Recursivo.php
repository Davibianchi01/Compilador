<?php

class Token {
    public string $tipo;
    public string $lexema;
    public int $linha;
    public int $coluna;
    public function __construct(string $tipo, string $lexema, int $linha, int $coluna) {
        $this->tipo = $tipo; $this->lexema = $lexema; $this->linha = $linha; $this->coluna = $coluna;
    }
    public function __toString(): string { return sprintf("<%s,%s> @%d:%d", $this->tipo, $this->lexema, $this->linha, $this->coluna); }
}

class Lexer {
    private string $src; private int $i=0; private int $linha=1; private int $coluna=1; private int $n;
    private array $kw = [ 'func'=>'FUNC', 'var'=>'VAR', 'if'=>'IF', 'else'=>'ELSE', 'write'=>'WRITE', 'read'=>'READ' ];
    public function __construct(string $src){ $this->src=$src; $this->n=strlen($src); }
    private function peek(int $k=0): string { $p=$this->i+$k; return $p<$this->n ? $this->src[$p] : "\0"; }
    private function adv(): string { $ch=$this->peek(); $this->i++; if($ch=="\n"){ $this->linha++; $this->coluna=1; } else { $this->coluna++; } return $ch; }
    private function addTok(array &$out, string $tipo, string $lex, int $li, int $co){ $out[] = new Token($tipo,$lex,$li,$co); }
    public function tokenize(): array {
        $toks=[];
        while(true){
            $ch=$this->peek(); if($ch=="\0") break;
            if(ctype_space($ch)){ $this->adv(); continue; }
            if($ch=='/' && $this->peek(1)=='/'){ while($ch!="\n" && $ch!="\0"){ $ch=$this->adv(); } continue; }
            if($ch=='/' && $this->peek(1)=='*'){ $this->adv();$this->adv(); while(!($this->peek()=='*'&&$this->peek(1)=='/') && $this->peek()!="\0"){ $this->adv(); } if($this->peek()=='*'){ $this->adv();$this->adv(); } continue; }
            $li=$this->linha; $co=$this->coluna;
            if(ctype_alpha($ch) || $ch=='_'){
                $lex=''; while(ctype_alnum($this->peek()) || $this->peek()=='_'){ $lex.=$this->adv(); }
                if($lex==='') { $lex=$this->adv(); }
                $tipo = $this->kw[$lex] ?? 'ID'; $this->addTok($toks,$tipo,$lex,$li,$co); continue;
            }
            if(ctype_digit($ch)){
                $lex=''; while(ctype_digit($this->peek())){ $lex.=$this->adv(); }
                if($this->peek()=='.' && ctype_digit($this->peek(1))){ $lex.=$this->adv(); while(ctype_digit($this->peek())){ $lex.=$this->adv(); } $this->addTok($toks,'NUM_FLOAT',$lex,$li,$co); }
                else { $this->addTok($toks,'NUM_INT',$lex,$li,$co); }
                continue;
            }
            $two=$ch.$this->peek(1);
            if(in_array($two,["==","!=","<=",">="])) { $this->adv(); $this->adv(); $this->addTok($toks,'RELOP',$two,$li,$co); continue; }
            switch($ch){
                case '+': case '-': case '*': case '/': $this->adv(); $this->addTok($toks,'OP',$ch,$li,$co); break;
                case '=': $this->adv(); $this->addTok($toks,'ATR',$ch,$li,$co); break;
                case '(': case ')': case '{': case '}': case ',': case ';': $this->adv(); $this->addTok($toks,$ch,$ch,$li,$co); break;
                case '<': case '>': $this->adv(); $this->addTok($toks,'RELOP',$ch,$li,$co); break;
                default: throw new \RuntimeException("Caractere inválido '$ch' em $li:$co");
            }
        }
        $toks[] = new Token('EOF','', $this->linha, $this->coluna);
        return $toks;
    }
}

class Parser {
    private array $toks; private int $i=0; private Token $la;
    public function __construct(array $toks){ $this->toks=$toks; $this->la=$toks[0]; }
    private function at(string $tipo, ?string $lex=null): bool { return $this->la->tipo===$tipo && ($lex===null || $this->la->lexema===$lex); }
    private function eat(string $tipo, ?string $lex=null): Token { if(!$this->at($tipo,$lex)) $this->err("Esperado $tipo".(isset($lex)?" '$lex'":"")); $t=$this->la; $this->i++; $this->la=$this->toks[$this->i]; return $t; }
    private function err(string $msg){ throw new \RuntimeException($msg." em ".$this->la->linha.":".$this->la->coluna." perto de '".$this->la->lexema."'"); }

    public function parseProgram(){ $nodes=[]; while(!$this->at('EOF')){ $nodes[]=$this->declOrStmt(); } return ['type'=>'Program','body'=>$nodes]; }

    private function declOrStmt(){ if($this->at('FUNC')) return $this->funcDecl(); if($this->at('VAR')){ $n=$this->varDecl(); $this->eat(';'); return $n; } return $this->stmt(); }

    private function funcDecl(){ $this->eat('FUNC'); $name=$this->eat('ID')->lexema; $this->eat('('); $params=[]; if($this->at('ID')){ $params[]=$this->eat('ID')->lexema; while($this->at(',')){ $this->eat(','); $params[]=$this->eat('ID')->lexema; } } $this->eat(')'); $body=$this->block(); return ['type'=>'FuncDecl','name'=>$name,'params'=>$params,'body'=>$body]; }

    private function varDecl(){ $this->eat('VAR'); $name=$this->eat('ID')->lexema; $init=null; if($this->at('ATR')){ $this->eat('ATR'); $init=$this->expr(); } return ['type'=>'VarDecl','name'=>$name,'init'=>$init]; }

    private function stmt(){ if($this->at('{')) return $this->block(); if($this->at('IF')) return $this->ifStmt(); if($this->at('WRITE')){ $n=$this->writeStmt(); $this->eat(';'); return $n; } if($this->at('READ')){ $n=$this->readStmt(); $this->eat(';'); return $n; } if($this->at('ID') && $this->toks[$this->i+1]->tipo==='ATR'){ $n=$this->assign(); $this->eat(';'); return $n; } $this->err('Comando inválido'); }

    private function block(){ $this->eat('{'); $items=[]; while(!$this->at('}')){ $items[]=$this->declOrStmt(); } $this->eat('}'); return ['type'=>'Block','body'=>$items]; }

    private function assign(){ $id=$this->eat('ID')->lexema; $this->eat('ATR'); $value=$this->expr(); return ['type'=>'Assign','id'=>$id,'value'=>$value]; }

    private function writeStmt(){ $this->eat('WRITE'); $this->eat('('); $e=$this->expr(); $this->eat(')'); return ['type'=>'Write','expr'=>$e]; }

    private function readStmt(){ $this->eat('READ'); $this->eat('('); $id=$this->eat('ID')->lexema; $this->eat(')'); return ['type'=>'Read','id'=>$id]; }

    private function ifStmt(){ $this->eat('IF'); $this->eat('('); $cond=$this->boolExpr(); $this->eat(')'); $then=$this->stmt(); $else=null; if($this->at('ELSE')){ $this->eat('ELSE'); $else=$this->stmt(); } return ['type'=>'If','cond'=>$cond,'then'=>$then,'else'=>$else]; }

    private function boolExpr(){ $l=$this->expr(); if(!$this->at('RELOP')) $this->err('Operador relacional esperado'); $op=$this->eat('RELOP')->lexema; $r=$this->expr(); return ['type'=>'Rel','op'=>$op,'left'=>$l,'right'=>$r]; }

    private function expr(){ $n=$this->term(); while($this->at('OP','+')||$this->at('OP','-')){ $op=$this->eat('OP')->lexema; $r=$this->term(); $n=['type'=>'Bin','op'=>$op,'left'=>$n,'right'=>$r]; } return $n; }

    private function term(){ $n=$this->factor(); while($this->at('OP','*')||$this->at('OP','/')){ $op=$this->eat('OP')->lexema; $r=$this->factor(); $n=['type'=>'Bin','op'=>$op,'left'=>$n,'right'=>$r]; } return $n; }

    private function factor(){ if($this->at('NUM_INT')||$this->at('NUM_FLOAT')){ $t=$this->la; $this->eat($t->tipo); return ['type'=>'Num','value'=>$t->lexema]; } if($this->at('ID')){ $id=$this->eat('ID')->lexema; return ['type'=>'Id','name'=>$id]; } if($this->at('(')){ $this->eat('('); $e=$this->expr(); $this->eat(')'); return $e; } $this->err('Fator inválido'); }
}

if (PHP_SAPI === 'cli' && isset($argv[1])){
    $code = file_get_contents($argv[1]);
    $lexer = new Lexer($code);
    $tokens = $lexer->tokenize();
    $parser = new Parser($tokens);
    $ast = $parser->parseProgram();
    fwrite(STDOUT, json_encode($ast, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");
}
