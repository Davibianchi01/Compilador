<?php
class SemanticActions {
    private $sem;

    public function __construct(SemanticAnalyzer $sem) {
        $this->sem = $sem;
    }

    public function onShiftToken($tok, $lexeme, $line, $col) {
        return [
            'token' => $tok,
            'lexeme' => $lexeme,
            'line' => $line,
            'col' => $col
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
