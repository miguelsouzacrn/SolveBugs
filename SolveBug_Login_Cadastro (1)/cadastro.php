<?php
session_start();
require_once __DIR__ . "/config/conexao.php";

$erro = "";
$sucesso = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST["nome"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";
    $confirmar = $_POST["confirmar_senha"] ?? "";

    if ($nome === "" || $email === "" || $senha === "" || $confirmar === "") {
        $erro = "Preencha todos os campos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Digite um e-mail válido.";
    } elseif (strlen($senha) < 6) {
        $erro = "A senha deve possuir pelo menos 6 caracteres.";
    } elseif ($senha !== $confirmar) {
        $erro = "As senhas não coincidem.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $erro = "Este e-mail já está cadastrado.";
        } else {
            $hash = password_hash($senha, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                "INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, 'usuario')"
            );
            $stmt->execute([$nome, $email, $hash]);

            $sucesso = "Cadastro realizado com sucesso! Agora você já pode entrar.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SolveBug - Cadastro</title>
    <link rel="stylesheet" href="css/auth.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>
    <a href="index.php" class="Btn" aria-label="Voltar">
        <div class="sign">
            <svg viewBox="0 0 512 512"><path d="M377.9 105.9L500.7 228.7c7.2 7.2 11.3 17.1 11.3 27.3s-4.1 20.1-11.3 27.3L377.9 406.1c-6.4 6.4-15 9.9-24 9.9c-18.7 0-33.9-15.2-33.9-33.9l0-62.1-128 0c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32l128 0 0-62.1c0-18.7 15.2-33.9 33.9-33.9c9 0 17.6 3.6 24 9.9zM160 96L96 96c-17.7 0-32 14.3-32 32l0 256c0 17.7 14.3 32 32 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-64 0c-53 0-96-43-96-96L0 128C0 75 43 32 96 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32z"/></svg>
        </div>
        <div class="text">Voltar</div>
    </a>

    <img class="imgLogo" src="img/9a5935eb-8429-404b-97a4-a3c728bd4573.png" alt="Logo SolveBug">

    <main class="container">
        <form method="POST" action="cadastro.php" autocomplete="off">
            <h1>Cadastro</h1>

            <?php if ($erro): ?>
                <div class="alerta erro"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <?php if ($sucesso): ?>
                <div class="alerta sucesso"><?= htmlspecialchars($sucesso) ?></div>
            <?php endif; ?>

            <div class="input-box">
                <input type="text" name="nome" placeholder="Usuário" required maxlength="100"
                       value="<?= htmlspecialchars($_POST["nome"] ?? "") ?>">
                <i class="bx bxs-user"></i>
            </div>

            <div class="input-box">
                <input type="email" name="email" placeholder="Email" required maxlength="150"
                       value="<?= htmlspecialchars($_POST["email"] ?? "") ?>">
                <i class="fa-solid fa-envelope"></i>
            </div>

            <div class="input-box">
                <input type="password" name="senha" placeholder="Senha" required minlength="6">
                <i class="bx bxs-lock-alt"></i>
            </div>

            <div class="input-box">
                <input type="password" name="confirmar_senha" placeholder="Confirmar senha" required minlength="6">
                <i class="bx bxs-lock-alt"></i>
            </div>

            <div class="container-botao">
                <button class="login" type="submit">Cadastrar</button>
            </div>

            <div class="registra-link">
                <p>Já tem uma conta? <a href="login.php">Entrar</a></p>
            </div>
        </form>
    </main>
</body>
</html>
