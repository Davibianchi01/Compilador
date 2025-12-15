<?php
class SemanticActions {
    private $sem;

    public function __construct(SemanticAnalyzer $sem) {
        $this->sem = $sem;
    }

    public function onShiftToken($tok, $lexeme, $line, $pos) {
        echo "Shift Token: $tok ('$lexeme') linha: $line, pos: $pos\n";
        return [
            'token' => $tok,
            'lexeme' => $lexeme,
            'line' => $line,
            'pos' => pos
        ];
    }

    public function reduce($ruleId, $rhs) {
        // EXEMPLO mínimo: declaração INT ID
        if ($ruleId === 14 && count($rhs) === 2) {
            $tipo = $rhs[0]['token']; // INT
            $id   = $rhs[1]['lexeme']; // x
            $this->sem->declareVariable($id, $tipo);
        }
        return null;
    }
}