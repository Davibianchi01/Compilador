<?php
class AnalisadorSintaticoAscendenteSLR {
    function parser(Lexico $lexico, $debug = false): bool {

        echo "=== Início do parser SLR ===\n";

        require_once __DIR__ . '/AnalisadorSemantico.php';
        
        $sem  = new SemanticAnalyzer();
        $acts = new SemanticActions($sem);

        // Definição das regras
        $regras = [
            1 => ['S', ['<PROGRAMA>']],
            2 => ['PROGRAMA', ['ID', 'AP', '<PARAMETROS>', 'FP', '<BLOCO>']],
            3 => ['COMANDO', ['<IF>']],
            4 => ['COMANDO', ['<WHILE>']],
            5 => ['COMANDO', ['<FOR>']],
            6 => ['COMANDO', ['<EXP>', 'PV']],
            7 => ['COMANDO', ['<FUNC>']],
            8 => ['COMANDO', ['<ATR>']],
            9 => ['COMANDO', ['<PRINTF>']],
            10 => ['COMANDO', ['<SCANF>']],
        ];

        // Definição do AFD (LR Table)
        $afd = [
            0  => ['ACTION'=>['ID'=>'S2'], 'GOTO'=>['<PROGRAMA>'=>1]],
            1  => ['ACTION'=>["$" => "ACC"], 'GOTO'=>[]],
            2  => ['ACTION'=>['AP'=>'S3'], 'GOTO'=>[]],
            3  => ['ACTION'=>['INT'=>'S9','BOOLEAN'=>'S10','STRING'=>'S11','FLOAT'=>'S12','FP'=>'R19','ID'=>'S8'],
                   'GOTO'=>['<PARAMETROS>'=>4,'<PARAMETRO>'=>6,'<TIPO>'=>7,'<LIST_PAM>'=>5]],
        ];

        // Pré-processamento das regras
        $PROD_LHS = []; $PROD_LEN = [];
        foreach ($regras as $rid => $r) {
            [$lhs, $rhs]     = $r;
            $PROD_LHS[$rid]  = trim($lhs, '<> ');
            $PROD_LEN[$rid]  = count($rhs);
        }

        $production_rhs_len  = fn($rid) => $PROD_LEN[$rid] ?? 0;
        $production_lhs_name = fn($rid) => $PROD_LHS[$rid] ?? ('NT_'.$rid);

        $scanner_next = function() use ($lexico) {
            $t = $lexico->nextToken();
            if ($t === null) return ['tok'=>'$', 'lexeme'=>null, 'line'=>null, 'col'=>null];

            $getFromObj = function($obj, array $props, array $methods = []) {
                foreach ($props as $p) if (isset($obj->$p)) return $obj->$p;
                foreach ($methods as $m) if (method_exists($obj, $m)) return $obj->$m();
                return null;
            };

            if (is_array($t)) {
                return [
                    'tok'    => strtoupper($t['token'] ?? $t['type'] ?? '$'),
                    'lexeme' => $t['lexeme'] ?? $t['value'] ?? null,
                    'line'   => $t['line'] ?? $t['linha'] ?? null,
                    'col'    => $t['col'] ?? $t['column'] ?? null
                ];
            }

            if (is_object($t)) {
                return [
                    'tok'    => strtoupper($getFromObj($t, ['token','tok','type'], ['getToken','getType']) ?? '$'),
                    'lexeme' => $getFromObj($t, ['lexeme','value'], ['getLexeme','getValue']),
                    'line'   => $getFromObj($t, ['line','linha'], ['getLine','getLinha']),
                    'col'    => $getFromObj($t, ['col','column'], ['getCol','getColumn'])
                ];
            }

            return ['tok'=>'$', 'lexeme'=>null, 'line'=>null, 'col'=>null];
        };

        $action_lookup = fn($state, $tok) => $afd[$state]['ACTION'][$tok] ?? null;
        $goto_lookup   = function($state, $lhs) use ($afd) {
            $name = trim($lhs,'<> ');
            foreach (["<$name>", $name, strtoupper($name), strtolower($name)] as $k)
                if(isset($afd[$state]['GOTO'][$k])) return $afd[$state]['GOTO'][$k];
            return null;
        };

        $stateStack = [0];
        $valStack   = [];
        $lookahead  = null;

        // Loop principal LR
        while (true) {
            if ($lookahead === null) $lookahead = $scanner_next();

            $s   = end($stateStack);
            $tok = $lookahead['tok'];
            $action = $action_lookup($s, $tok);

            // Debug: estado e token
            echo "\n---\nEstado atual: $s\n";
            echo "Lookahead token: $tok | Lexema: " . ($lookahead['lexeme'] ?? 'null') . " | Linha: " . ($lookahead['line'] ?? '?') . ", Col: " . ($lookahead['col'] ?? '?') . "\n";
            echo "Stack estados: [" . implode(',', $stateStack) . "]\n";
            echo "Stack valores: [" . implode(',', array_map(fn($v) => $v['name'] ?? $v, $valStack)) . "]\n";
            echo "Ação da tabela LR: " . ($action ?? 'NULL') . "\n";

            // Erro sintático
            if ($action === null) {
                if ($tok === '$' && $s === 1) {
                    echo "=== Aceito no final ===\n";
                    if ($sem->hasErrors()) { 
                        echo "Erros semânticos encontrados:\n"; 
                        $sem->printErrors();
                        return false; 
                    }
                    return true;
                }
                echo "=== Erro sintático na linha " . ($lookahead['line'] ?? '?') . ": token '$tok' (estado $s) ===\n";
                if ($sem->hasErrors()) { echo "Erros semânticos encontrados:\n"; $sem->printErrors(); }
                return false;
            }

            // Shift
            if ($action[0] === 'S') {
                $stateStack[] = intval(substr($action,1));
                $valStack[]   = $acts->onShiftToken($tok, $lookahead['lexeme'], $lookahead['line'], $lookahead['col']);
                echo ">> Shift -> empilhado estado " . end($stateStack) . "\n";
                $lookahead    = null;
                continue;
            }

            // Reduce
            if ($action[0] === 'R') {
                $rid    = intval(substr($action,1));
                $rhsLen = $production_rhs_len($rid);
                $lhsSym = $production_lhs_name($rid);

                $rhs = [];
                for ($i=0; $i<$rhsLen; $i++) {
                    array_pop($stateStack);
                    $rhs[] = array_pop($valStack);
                }
                $rhs = array_reverse($rhs);

                echo ">> Reduce usando regra R$rid -> $lhsSym | RHS: [" . implode(',', array_map(fn($v) => $v['name'] ?? $v, $rhs)) . "]\n";

                $lhsAttr = $acts->reduce($rid, $rhs) ?? sem_attr('NT', $lhsSym);
                $lhsAttr['name'] = $lhsSym;

                $goto = $goto_lookup(end($stateStack), $lhsSym);
                if ($goto === null) {
                    echo "Erro: GOTO faltando para $lhsSym a partir do estado " . end($stateStack) . "\n";
                    if ($sem->hasErrors()) $sem->printErrors();
                    return false;
                }

                $stateStack[] = $goto;
                $valStack[]   = $lhsAttr;
                echo ">> Próximo estado após reduce: $goto\n";
                continue;
            }

            // Accept
            if ($action === 'ACC') {
                echo "=== Aceito! Compilação sintática concluída ===\n";
                if ($sem->hasErrors()) { 
                    echo "Erros semânticos encontrados:\n"; 
                    $sem->printErrors(); 
                    return false; 
                }
                return true;
            }

            echo "Ação inválida '$action'\n";
            if ($sem->hasErrors()) $sem->printErrors();
            return false;
        }
    }
}