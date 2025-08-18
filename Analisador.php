<?php
/***
 *  COMANDO -> ATR | DEC 
 *  ATR -> id atr EXP pv
 *  EXP -> id | const
 *  DEC -> tipo id pv
 */
class DescendenteRecursivo{
    private $cont = 0;
    public $lista_tokens = [];

    /**
     * Função validar o terminal esperado com a lista de tokens
     * @param mixed $token token esperado para validar na gramática
     * @return bool
     */
    function term($token):bool{
        return $this->lista_tokens[$this->cont++] == $token;
    }
    /**
     * COMANDO -> ATR | DEC 
     * @return bool
     */
    function comando(){
        echo "COMANDO -> ATR | DEC \n";
        $anterior = $this->cont;
        if ($this->atr())
            return true;
        else{
            $this->cont = $anterior;
            return $this->dec();
        }
    }

    /**
     * ATR -> id atr EXP pv
     * @return bool
     */
    function atr(){
        echo "ATR -> id atr EXP pv\n";
        return $this->term("id") && $this->term("atr") && $this->expe() && $this->term("pv");
    }

    /**
     * EXP -> id | const
     * @return bool
     */
    function expe(){
        echo "EXP -> id | const\n";
        $anterior = $this->cont;
        if ( $this->exp1())
            return true;
        else{
            $this->cont = $anterior;
            return  $this->exp2();
        }
    }
    /**
     * EXP -> id 
     * @return bool
     */
    function exp1(){
        echo "EXP -> id \n";
        return $this->term("id");
    }
    /**
     * EXP -> const 
     * @return bool
     */
    function exp2(){
        echo "EXP -> const\n ";
        return $this->term("const");
    }
    /**
     * DEC -> tipo id pv
     * @return bool
     */
    function dec(){
        echo "DEC -> tipo id pv\n";
        return $this->term("tipo") && $this->term("id") && $this->term("pv");
    }
}

$sintatico = new DescendenteRecursivo();
$sintatico->lista_tokens = ['id','atr','const','pv'];
if ($sintatico->comando())
    echo "linguagem aceita";
else
    echo "erro sintático";
?>