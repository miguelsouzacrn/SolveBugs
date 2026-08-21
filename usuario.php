<?php
require_once __DIR__ . "/config/auth.php";
require_once __DIR__ . "/config/conn.php";
exigirLogin();

$stmt = $pdo->prepare("SELECT id, nome, email, tipo, data_cadastro FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION["usuario_id"]]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header("Location: logout.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SolveBug - Usuário</title>
<link rel="stylesheet" href="css/Usuario2.css">
</head>
<body>
<a class="close-btn" href="index.php">Voltar</a>

<div class="container">
    <aside class="sidebar">
        <div class="profile">
            <img src="img/perfilIcon.avif" alt="Perfil">
            <h3><?= htmlspecialchars($usuario["nome"]) ?></h3>
            <p><?= htmlspecialchars($usuario["email"]) ?></p>
        </div>

        <ul class="menu">
            <li class="ativo">⭐ Minha conta</li>
            <li onclick="mostrarInfo()">👤 Dados da conta</li>
            <li><a href="logout.php">🚪 Sair</a></li>
        </ul>
    </aside>

    <main class="main">
        <div class="header">
            <div>
                <h2>Minha conta</h2>
                <p class="subtitulo">Dados cadastrados no SolveBug</p>
            </div>
        </div>

        <section class="solucoes">
            <div class="card">
                <h3>Nome de usuário</h3>
                <p><?= htmlspecialchars($usuario["nome"]) ?></p>
            </div>
            <div class="card">
                <h3>E-mail</h3>
                <p><?= htmlspecialchars($usuario["email"]) ?></p>
            </div>
            <div class="card">
                <h3>Tipo de conta</h3>
                <p><?= htmlspecialchars($usuario["tipo"]) ?></p>
            </div>
            <div class="card">
                <h3>Cadastro</h3>
                <p><?= date("d/m/Y H:i", strtotime($usuario["data_cadastro"])) ?></p>
            </div>
        </section>
    </main>
</div>

<script>
function mostrarInfo() {
    alert("Esta área será expandida com edição de perfil na próxima etapa.");
}
</script>
</body>
</html>
