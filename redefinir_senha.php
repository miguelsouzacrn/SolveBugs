<?php

session_start();

// ==========================================
// CONEXÃO
// ==========================================

$conn = new mysqli(
    "localhost",
    "root",
    "",
    "solvebugs"
);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// ==========================================
// TOKEN
// ==========================================

$token = trim($_GET["token"] ?? "");

$erro = "";
$sucesso = "";

$tokenValido = false;
$recuperacao = null;

if ($token === "") {

    $erro = "Link de recuperação inválido.";

} elseif (!preg_match('/^[a-f0-9]{64}$/', $token)) {

    $erro = "Link de recuperação inválido.";

} else {

    // ==========================================
    // PROCURA TOKEN
    // ==========================================

    $stmt = $conn->prepare("
        SELECT
            id,
            usuario_id,
            expiracao,
            usado
        FROM recuperacao_senha
        WHERE token = ?
        LIMIT 1
    ");

    $stmt->bind_param("s", $token);
    $stmt->execute();

    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {

        $recuperacao = $resultado->fetch_assoc();

        // ==========================================
        // VERIFICA SE JÁ FOI USADO
        // ==========================================

        if ((int)$recuperacao["usado"] === 1) {

            $erro = "Este link já foi utilizado.";

        // ==========================================
        // VERIFICA EXPIRAÇÃO
        // ==========================================

        } elseif (strtotime($recuperacao["expiracao"]) < time()) {

            $erro = "Este link de recuperação expirou.";

        } else {

            $tokenValido = true;
        }

    } else {

        $erro = "Link de recuperação inválido.";
    }

    $stmt->close();
}

// ==========================================
// ALTERAÇÃO DA SENHA
// ==========================================

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && $tokenValido
) {

    $senha = $_POST["senha"] ?? "";
    $confirmarSenha = $_POST["confirmar_senha"] ?? "";

    // ==========================================
    // VALIDAÇÃO
    // ==========================================

    if ($senha === "" || $confirmarSenha === "") {

        $erro = "Preencha os dois campos.";

    } elseif (strlen($senha) < 8) {

        $erro = "A senha deve possuir pelo menos 8 caracteres.";

    } elseif ($senha !== $confirmarSenha) {

        $erro = "As senhas não coincidem.";

    } else {

        // ==========================================
        // GERA HASH
        // ==========================================

        $novoHash = password_hash(
            $senha,
            PASSWORD_DEFAULT
        );

        // ==========================================
        // ATUALIZA SENHA
        // ==========================================

        $update = $conn->prepare("
            UPDATE usuarios
            SET senha = ?
            WHERE id = ?
        ");

        $update->bind_param(
            "si",
            $novoHash,
            $recuperacao["usuario_id"]
        );

        if ($update->execute()) {

            // ==========================================
            // INVALIDA TOKEN
            // ==========================================

            $invalidar = $conn->prepare("
                UPDATE recuperacao_senha
                SET usado = 1
                WHERE id = ?
            ");

            $invalidar->bind_param(
                "i",
                $recuperacao["id"]
            );

            $invalidar->execute();
            $invalidar->close();

            // ==========================================
            // REMOVE OUTROS TOKENS DO USUÁRIO
            // ==========================================

            $limpar = $conn->prepare("
                DELETE FROM recuperacao_senha
                WHERE usuario_id = ?
                  AND id <> ?
            ");

            $limpar->bind_param(
                "ii",
                $recuperacao["usuario_id"],
                $recuperacao["id"]
            );

            $limpar->execute();
            $limpar->close();

            $sucesso =
                "Sua senha foi alterada com sucesso.";

            $tokenValido = false;

        } else {

            $erro =
                "Não foi possível alterar a senha.";
        }

        $update->close();
    }
}

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        SolveBugs - Nova senha
    </title>

    <link
        rel="stylesheet"
        href="./css/redefinir_senha.css"
    >

    <link
        href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css"
        rel="stylesheet"
    >

</head>

<body>

    <div class="fundo"></div>

    <img
        class="imgLogo"
        src="img/9a5935eb-8429-404b-97a4-a3c728bd4573.png"
        alt="Logo SolveBugs"
    >

    <main class="container">

        <?php if ($sucesso): ?>

            <h1>Senha alterada!</h1>

            <div class="alerta sucesso">
                <?= htmlspecialchars($sucesso) ?>
            </div>

            <a
                href="login.php"
                class="botao-link"
            >
                Voltar para o login
            </a>

        <?php elseif ($tokenValido): ?>

            <form method="POST">

                <h1>Nova senha</h1>

                <p class="descricao">
                    Digite sua nova senha abaixo.
                </p>

                <?php if ($erro): ?>

                    <div class="alerta erro">
                        <?= htmlspecialchars($erro) ?>
                    </div>

                <?php endif; ?>

                <div class="input-box">

                    <input
                        type="password"
                        name="senha"
                        placeholder="Nova senha"
                        minlength="8"
                        required
                    >

                    <i class="bx bxs-lock-alt"></i>

                </div>

                <div class="input-box">

                    <input
                        type="password"
                        name="confirmar_senha"
                        placeholder="Confirmar nova senha"
                        minlength="8"
                        required
                    >

                    <i class="bx bxs-lock-alt"></i>

                </div>

                <div class="container-botao">

                    <button
                        type="submit"
                        class="recuperar"
                    >
                        Alterar senha
                    </button>

                </div>

            </form>

        <?php else: ?>

            <h1>Link inválido</h1>

            <div class="alerta erro">
                <?= htmlspecialchars($erro) ?>
            </div>

            <a
                href="recuperar_senha.php"
                class="botao-link"
            >
                Solicitar novo link
            </a>

        <?php endif; ?>

    </main>

</body>

</html>