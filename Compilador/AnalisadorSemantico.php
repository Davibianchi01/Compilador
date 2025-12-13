<?php
class AnalisadorSemantico {
    //Classe realiza a análise semântica do código fonte e verifica: Declaração e uso de variáveis / Escopos e visibilidade de símbolos / Tipos de variáveis 

    public $message; public $line; public $col;
    public function __construct($message, $line = null, $col = null) {
        $this->message = $message; $this->line = $line; $this->col = $col;
    }
    public function __toString() {
        if ($this->line !== null) return "{$this->message} (linha {$this->line}, col {$this->col})";
        return $this->message;
    }
}

class Symbol {
    public $name; public $type; public $meta;
    public function __construct($name, $type, $meta = []) {
        $this->name = $name;
        $this->type = strtoupper($type);
        $this->meta = $meta;
    }
}

class SymbolTable {
    private $scopes;
    public function __construct() { $this->scopes = []; $this->enterScope(); }
    public function enterScope() { array_push($this->scopes, []); }
    public function leaveScope() { array_pop($this->scopes); if (count($this->scopes)===0) $this->enterScope(); }
    public function declare(Symbol $sym) {
        $current = &$this->scopes[count($this->scopes)-1];
        if (isset($current[$sym->name])) return false;
        $current[$sym->name] = $sym; return true;
    }
    public function lookup($name) {
        for ($i=count($this->scopes)-1; $i>=0; $i--) if (isset($this->scopes[$i][$name])) return $this->scopes[$i][$name];
        return null;
    }
    public function declaredInCurrentScope($name) {
        $current = $this->scopes[count($this->scopes)-1]; return isset($current[$name]);
    }
    public function currentScopeEntries() { return $this->scopes[count($this->scopes)-1]; }
}

class SemanticAnalyzer {
    private $symtab; private $errors;
    private $reservedWords = ['INT','BOOLEAN','STRING','FLOAT','IF','ELSE','WHILE','FOR','RETURN','VOID'];
    
    public function __construct() { 
        $this->symtab = new SymbolTable(); 
        $this->errors = []; 
    }

    public function getErrors() { return $this->errors; }
    public function hasErrors() { return count($this->errors) > 0; }
    public function reportError($msg, $line = null, $col = null) { $this->errors[] = new SemanticError($msg, $line, $col); }
    public function enterScope() { $this->symtab->enterScope(); }
    public function leaveScope() { $this->symtab->leaveScope(); }


    //Declaração de variável com validação de palavras reservadas
    public function declareVariable(string $name, string $type, $line = null, $col = null) {
        if (in_array(strtoupper($name), $this->reservedWords)) {
            $this->reportError("Variável '$name' não pode usar palavra reservada", $line, $col);
            return false;
        }
        $sym = new Symbol($name, $type, ['initialized'=>false]);
        $ok = $this->symtab->declare($sym);
        if (!$ok) $this->reportError("Redeclaração da variável '$name' no mesmo escopo", $line, $col);
        return $ok;
    }

    public function declareFunction(string $name, array $paramTypes, string $retType = 'VOID', $line = null, $col = null) {
        $meta = ['params' => $paramTypes, 'ret' => strtoupper($retType)];
        $sym = new Symbol($name, 'FUNC', $meta);
        $ok = $this->symtab->declare($sym);
        if (!$ok) $this->reportError("Redeclaração da função '$name' no mesmo escopo", $line, $col);
        return $ok;
    }

    public function useVariable(string $name, $line = null, $col = null) {
        $sym = $this->symtab->lookup($name);
        if ($sym === null) { 
            $this->reportError("Uso de variável não declarada: '$name'", $line, $col); 
            return null; 
        }
        if (!($sym->meta['initialized'] ?? true)) {
            $this->reportError("Variável '$name' usada antes de inicializada", $line, $col);
        }
        return $sym;
    }


    //Validação de atribuição
    public function checkAssignment(string $lhsName, $rhsTypeOrSymbol, $line = null, $col = null) {
        $lhsSym = $this->useVariable($lhsName, $line, $col); 
        if ($lhsSym === null) return false;

        $lhsType = strtoupper($lhsSym->type);
        $rhsType = ($rhsTypeOrSymbol instanceof Symbol) ? strtoupper($rhsTypeOrSymbol->type) : strtoupper((string)$rhsTypeOrSymbol);

        if (!$this->areTypesCompatible($lhsType, $rhsType)) {
            $this->reportError("Tipo incompatível na atribuição: '$lhsName' espera $lhsType mas expressão é $rhsType", $line, $col);
            return false;
        }

        $lhsSym->meta['initialized'] = true;
        return true;
    }

    //Compatibilidade de tipos para atribuições
    public function areTypesCompatible(string $target, string $source) {
        if ($target === $source) return true;

        $intTypes = ['INT','CONST'];
        $floatTypes = ['FLOAT','INT','CONST','PONTOFLUTUANTE'];
        $strTypes = ['STRING'];
        $boolTypes = ['BOOLEAN'];

        switch ($target) {
            case 'INT': return in_array($source,$intTypes);
            case 'FLOAT': return in_array($source,$floatTypes);
            case 'STRING': return in_array($source,$strTypes);
            case 'BOOLEAN': return in_array($source,$boolTypes);
            default: return false;
        }
    }

    public function checkCondition($exprTypeOrSymbol, $line = null, $col = null) {
        $type = $exprTypeOrSymbol instanceof Symbol ? strtoupper($exprTypeOrSymbol->type) : strtoupper((string)$exprTypeOrSymbol);
        if ($type !== 'BOOLEAN') {
            $this->reportError("Expressão de condição não booleana (tipo: $type)", $line, $col);
            return false;
        }
        return true;
    }

    public function checkPrintfName(string $name, $line = null, $col = null) {
        $sym = $this->symtab->lookup($name);
        if ($sym === null) $this->reportError("printf/scanf: variável '$name' não declarada", $line, $col);
    }

    public function registerVarFromReduction(string $tipo, string $name, $line=null, $col=null) {
        return $this->declareVariable($name, $tipo, $line, $col);
    }

    public function registerAssignFromReduction(string $lhsName, $rhsNodeOrType, $line=null, $col=null) {
        $rhsType = strtoupper((string)$rhsNodeOrType);
        return $this->checkAssignment($lhsName, $rhsType, $line, $col);
    }

    public function printErrors() { foreach ($this->errors as $e) echo $e->__toString() . PHP_EOL; }
    public function reset() { $this->symtab = new SymbolTable(); $this->errors = []; }
}

//Funções auxiliares: Tipos / Tokens / Inferência de expressões
function sem_attr($kind, $name, $lexeme=null, $line=null, $col=null, $semType=null, $extra=null) {
    return ['kind'=>$kind,'name'=>$name,'lexeme'=>$lexeme,'line'=>$line,'col'=>$col,'semType'=>$semType,'extra'=>$extra];
}

function default_token_semtype($tok, $lexeme) {
    $tok = strtoupper($tok);
    switch ($tok) {
        case 'ID': return 'ID';
        case 'CONST': return 'CONST';
        case 'PONTOFLUTUANTE': return 'FLOAT';
        case 'INT': case 'BOOLEAN': case 'STRING': case 'FLOAT': return $tok;
        default: return null;
    }
}

function infer_binop_type($op, $lt, $rt) {
    $op = strtoupper($op); $lt = strtoupper((string)$lt); $rt = strtoupper((string)$rt);
    if (in_array($op, ['MAIOR','MENOR','MAIORIGUAL','MENORIGUAL','IGUALIGUAL','DIFERENTE'])) return 'BOOLEAN';
    if (in_array($op, ['SOMA','SUBTRACAO','MULTIPLICACAO','DIVISAO'])) {
        if (in_array($lt,['FLOAT','PONTOFLUTUANTE']) || in_array($rt,['FLOAT','PONTOFLUTUANTE'])) return 'FLOAT';
        if ($lt==='STRING' || $rt==='STRING') return 'STRING';
        return 'INT';
    }
    return null;
}