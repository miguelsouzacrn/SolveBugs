<?php
session_start();
require_once __DIR__ . "/config/conexao.php";

if (isset($_SESSION["usuario_id"])) {
    header("Location: index.php");
    exit;
}

$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";

    if ($email === "" || $senha === "") {
        $erro = "Informe o e-mail e a senha.";
    } else {
        $stmt = $pdo->prepare("SELECT id, nome, email, senha, tipo FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        $senhaValida = false;

        if ($usuario) {
            // Senhas novas usam password_hash().
            $senhaValida = password_verify($senha, $usuario["senha"]);

            // Compatibilidade com registros antigos do banco que estejam em texto puro.
            if (!$senhaValida && hash_equals((string)$usuario["senha"], $senha)) {
                $senhaValida = true;

                // Migra a senha antiga para hash automaticamente.
                $novoHash = password_hash($senha, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
                $update->execute([$novoHash, $usuario["id"]]);
            }
        }

        if ($senhaValida) {
            session_regenerate_id(true);

            $_SESSION["usuario_id"] = $usuario["id"];
            $_SESSION["usuario_nome"] = $usuario["nome"];
            $_SESSION["usuario_email"] = $usuario["email"];
            $_SESSION["usuario_tipo"] = $usuario["tipo"];

            header("Location: index.php");
            exit;
        } else {
            $erro = "E-mail ou senha incorretos.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SolveBug - Login</title>
    <link rel="stylesheet" href="css/auth.css">
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
        <form method="POST" action="login.php">
            <h1>Login</h1>

            <?php if ($erro): ?>
                <div class="alerta erro"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <div class="input-box">
                <input type="email" name="email" placeholder="Email" required maxlength="150"
                       value="<?= htmlspecialchars($_POST["email"] ?? "") ?>">
                <i class="bx bxs-envelope"></i>
            </div>

            <div class="input-box">
                <input type="password" name="senha" placeholder="Senha" required>
                <i class="bx bxs-lock-alt"></i>
            </div>

            <div class="lembrar-esqueci">
                <label>
                    <input type="checkbox" name="lembrar"> Lembrar senha
                </label>
                <a href="#" onclick="alert('A recuperação de senha será adicionada na próxima etapa.'); return false;">Esqueci a senha</a>
            </div>

            <div class="container-botao">
                <button class="login" type="submit">Entrar</button>
            </div>

            <div class="registra-link">
                <p>Não tem uma conta? <a href="cadastro.php">Cadastre-se</a></p>
            </div>
        </form>
    </main>
</body>
</html>
