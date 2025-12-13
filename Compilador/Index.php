<?php
require_once(__DIR__ . "/Lexico.php");
require_once(__DIR__ . "/AnalisadorSintaticoAscendenteSLR.php");
require_once(__DIR__ . "/AnalisadorSemantico.php");
require_once(__DIR__ . "/GeradorCodigoMIPS.php");

$resultado = "";
$saida = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = $_POST['codigo'] ?? "";

    ob_start(); // captura echo
    try {
        $compilador = new Compilador();
        $compilador->compilar($codigo);
    } catch (Throwable $e) {
        echo "Erro: " . $e->getMessage();
    }
    $saida = ob_get_clean();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Compilador PHP - Analisador LR</title>
<style>
body { font-family: Arial; margin: 20px; }
textarea { width: 100%; font-family: monospace; }
pre { background: #f8f8f8; padding: 10px; }
</style>
</head>
<body>

<h1>Compilador PHP - Analisador LR</h1>

<form method="post">
<textarea name="codigo" rows="10"><?= htmlspecialchars($_POST['codigo'] ?? '') ?></textarea>
<br>
<input type="submit" value="Analisar">
</form>

<?php if ($saida): ?>
<h2>Saída</h2>
<pre><?= htmlspecialchars($saida) ?></pre>
<?php endif; ?>

</body>
</html>
